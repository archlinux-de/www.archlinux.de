package home

import (
	"log/slog"
	"net/http"

	"www/internal/news"
	"www/internal/packages"
	"www/internal/ui/layout"
)

const (
	latestNewsCount     = 6
	recentPackagesCount = 20
)

type Handler struct {
	newsRepo *news.Repository
	pkgRepo  *packages.Repository
	manifest *layout.Manifest
}

func NewHandler(newsRepo *news.Repository, pkgRepo *packages.Repository, manifest *layout.Manifest) *Handler {
	return &Handler{newsRepo: newsRepo, pkgRepo: pkgRepo, manifest: manifest}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /{$}", h.index)
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	latestNews, err := h.newsRepo.Latest(r.Context(), latestNewsCount)
	if err != nil {
		slog.Error("failed to fetch latest news", "error", err)
	}

	recentPkgs, err := h.pkgRepo.LatestStable(r.Context(), recentPackagesCount)
	if err != nil {
		slog.Error("failed to fetch recent packages", "error", err)
	}

	page := layout.Page{
		Title:       "Start",
		Description: "Deutschsprachige Foren, Neuigkeiten, Pakete und ISO-Downloads zu Arch Linux",
		Path:        "/",
		Manifest:    h.manifest,
	}
	layout.Render(w, r, page, Index(latestNews, recentPkgs))
}
