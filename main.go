package main

import (
	"context"
	"log/slog"
	"net/http"
	"os"
	"time"

	"www/internal/config"
	"www/internal/countries"
	"www/internal/database"
	"www/internal/mirrors"
	"www/internal/news"
	"www/internal/packages"
	"www/internal/popularity"
	"www/internal/releases"
	"www/internal/ui"
	"www/internal/ui/httperror"
	uilayout "www/internal/ui/layout"
	"www/internal/web"
)

const defaultCacheMaxAge = 5 * time.Minute

func main() {
	cfg, err := config.Load()
	if err != nil {
		slog.Error("failed to load config", "error", err)
		os.Exit(1)
	}

	if len(os.Args) > 1 {
		os.Exit(runCommand(os.Args[1], cfg))
	}

	if err := run(cfg); err != nil {
		slog.Error("fatal error", "error", err)
		os.Exit(1)
	}
}

func runCommand(cmd string, cfg config.Config) int {
	logger := setupLogger(isDevelopment)
	slog.SetDefault(logger)

	db, err := database.New(cfg.Database)
	if err != nil {
		slog.Error("failed to open database", "error", err)
		return 1
	}
	defer func() { _ = db.Close() }()

	ctx := context.Background()

	switch cmd {
	case "update-packages":
		if err := packages.Update(ctx, db); err != nil {
			slog.Error("update-packages failed", "error", err)
			return 1
		}
	case "update-news":
		if err := news.Update(ctx, db); err != nil {
			slog.Error("update-news failed", "error", err)
			return 1
		}
	case "update-mirrors":
		if err := mirrors.Update(ctx, db); err != nil {
			slog.Error("update-mirrors failed", "error", err)
			return 1
		}
	case "update-releases":
		if err := releases.Update(ctx, db); err != nil {
			slog.Error("update-releases failed", "error", err)
			return 1
		}
	case "update-countries":
		if err := countries.Update(ctx, db); err != nil {
			slog.Error("update-countries failed", "error", err)
			return 1
		}
	case "update-popularities":
		if err := popularity.UpdatePackages(ctx, db); err != nil {
			slog.Error("update-popularities (packages) failed", "error", err)
			return 1
		}
		if err := popularity.UpdateMirrors(ctx, db); err != nil {
			slog.Error("update-popularities (mirrors) failed", "error", err)
			return 1
		}
	default:
		slog.Error("unknown command", "command", cmd)
		return 1
	}

	return 0
}

func run(cfg config.Config) error {
	logger := setupLogger(isDevelopment)
	slog.SetDefault(logger)

	db, err := database.New(cfg.Database)
	if err != nil {
		return err
	}
	defer func() { _ = db.Close() }()

	manifest, err := uilayout.NewManifest(embedManifest)
	if err != nil {
		return err
	}

	mux := http.NewServeMux()

	ui.RegisterRoutes(mux, manifest, db, embedAssets, embedStatic, embedRoot)

	var cacheMiddleware web.Middleware
	if isDevelopment {
		cacheMiddleware = web.NoCache()
	} else {
		cacheMiddleware = web.CacheControl(defaultCacheMaxAge)
	}

	handler := web.Chain(mux,
		web.Recovery(),
		web.SecureHeaders(),
		httperror.Middleware(manifest),
		cacheMiddleware,
	)

	server := web.NewServer(":"+cfg.Port, handler)
	return server.ListenAndServe()
}

func setupLogger(isDevelopment bool) *slog.Logger {
	var handler slog.Handler
	if isDevelopment {
		handler = slog.NewTextHandler(os.Stdout, &slog.HandlerOptions{
			Level: slog.LevelDebug,
		})
	} else {
		handler = slog.NewJSONHandler(os.Stdout, &slog.HandlerOptions{
			Level: slog.LevelInfo,
		})
	}
	return slog.New(handler)
}
