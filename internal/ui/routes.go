package ui

import (
	"fmt"
	"io/fs"
	"net/http"
	"strings"

	"www/internal/ui/home"
	"www/internal/ui/layout"
	"www/internal/ui/legal"
)

const (
	assetsCacheMaxAge = 31536000 // 1 year
	staticCacheMaxAge = 86400    // 1 day
)

func RegisterRoutes(
	mux *http.ServeMux,
	manifest *layout.Manifest,
	assets, static, root fs.FS,
) {
	home.NewHandler(manifest).RegisterRoutes(mux)
	legal.NewHandler(manifest).RegisterRoutes(mux)
	handleAssets(mux, assets)
	handleStatic(mux, static)
	handleFavicon(mux, root)
	handleManifest(mux, root)
	handleRobots(mux, root)
}

func handleFavicon(mux *http.ServeMux, root fs.FS) {
	mux.Handle("GET /favicon.ico", cacheHandler(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		http.ServeFileFS(w, r, root, "root/favicon.ico")
	}), staticCacheMaxAge))
}

func handleManifest(mux *http.ServeMux, root fs.FS) {
	mux.Handle("GET /manifest.webmanifest", cacheHandler(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Content-Type", "application/manifest+json")
		http.ServeFileFS(w, r, root, "root/manifest.webmanifest")
	}), staticCacheMaxAge))
}

func handleRobots(mux *http.ServeMux, root fs.FS) {
	mux.Handle("GET /robots.txt", cacheHandler(http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		http.ServeFileFS(w, r, root, "root/robots.txt")
	}), staticCacheMaxAge))
}

func handleAssets(mux *http.ServeMux, assets fs.FS) {
	sub, err := fs.Sub(assets, "dist/assets")
	if err != nil {
		panic(err)
	}
	fileServer := http.FileServer(http.FS(sub))
	mux.Handle("GET /assets/", http.StripPrefix("/assets/", cacheHandler(fileServer, assetsCacheMaxAge, "immutable")))
}

func handleStatic(mux *http.ServeMux, static fs.FS) {
	sub, err := fs.Sub(static, "static")
	if err != nil {
		panic(err)
	}
	fileServer := http.FileServer(http.FS(sub))
	mux.Handle("GET /static/", http.StripPrefix("/static/", cacheHandler(fileServer, staticCacheMaxAge)))
}

func cacheHandler(next http.Handler, maxAge int, directives ...string) http.Handler {
	value := fmt.Sprintf("public, max-age=%d", maxAge)
	if len(directives) > 0 {
		value += ", " + strings.Join(directives, ", ")
	}
	return http.HandlerFunc(func(w http.ResponseWriter, r *http.Request) {
		w.Header().Set("Cache-Control", value)
		next.ServeHTTP(w, r)
	})
}
