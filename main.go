package main

import (
	"log/slog"
	"net/http"
	"os"
	"time"

	"www/internal/config"
	"www/internal/database"
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

	if err := run(cfg); err != nil {
		slog.Error("fatal error", "error", err)
		os.Exit(1)
	}
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

	ui.RegisterRoutes(mux, manifest, embedAssets, embedStatic, embedRoot)

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
