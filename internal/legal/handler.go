package legal

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
	mux.HandleFunc("GET /privacy-policy", h.privacyPolicy)
	mux.HandleFunc("GET /impressum", h.impressum)
}

func (h *Handler) privacyPolicy(w http.ResponseWriter, r *http.Request) {
	page := layout.Page{
		Title:    "Datenschutz",
		Path:     "/privacy-policy",
		Manifest: h.manifest,
		NoIndex:  true,
	}
	layout.Render(w, r, page, PrivacyPolicy())
}

func (h *Handler) impressum(w http.ResponseWriter, r *http.Request) {
	page := layout.Page{
		Title:    "Impressum",
		Path:     "/impressum",
		Manifest: h.manifest,
		NoIndex:  true,
	}
	layout.Render(w, r, page, Impressum())
}
