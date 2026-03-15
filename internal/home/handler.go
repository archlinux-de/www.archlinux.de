package home

import (
	"net/http"

	"www/internal/ui/layout"
)

type Handler struct {
	manifest *layout.Manifest
}

func NewHandler(manifest *layout.Manifest) *Handler {
	return &Handler{manifest: manifest}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /{$}", h.index)
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	page := layout.Page{
		Title:       "Start",
		Description: "Deutschsprachige Foren, Neuigkeiten, Pakete und ISO-Downloads zu Arch Linux",
		Path:        "/",
		Manifest:    h.manifest,
	}
	layout.Render(w, r, page, Index())
}
