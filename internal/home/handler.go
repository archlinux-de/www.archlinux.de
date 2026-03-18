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

	baseURL := layout.GetBaseURL(r)
	page := layout.Page{
		Title:       "Start",
		Description: "Deutschsprachige Foren, Neuigkeiten, Pakete und ISO-Downloads zu Arch Linux",
		Path:        "/",
		Manifest:    h.manifest,
		JsonLD: map[string]any{
			"@context":      "https://schema.org",
			"@type":         "WebSite",
			"name":          "archlinux.de",
			"alternateName": "Arch Linux Deutschland",
			"url":           baseURL + "/",
			"potentialAction": map[string]any{
				"@type":       "SearchAction",
				"target":      map[string]any{"@type": "EntryPoint", "urlTemplate": baseURL + "/packages?search={search}"},
				"query-input": "required name=search",
			},
		},
	}
	layout.Render(w, r, page, Index(latestNews, recentPkgs))
}
