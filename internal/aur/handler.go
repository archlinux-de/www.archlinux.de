package aur

import (
	"net/http"
	"net/url"
	"regexp"
	"strings"
	"time"

	"archded/internal/ui/layout"
)

var packageNameRE = regexp.MustCompile(`^[A-Za-z0-9@_+][A-Za-z0-9@._+-]{0,255}$`)

const (
	aurPath                     = "/aur"
	aurURL                      = "https://aur.archlinux.org/"
	acknowledgementCookie       = "aur_acknowledged"
	acknowledgementCookieValue  = "true"
	acknowledgementCookieMaxAge = int((365 * 24 * time.Hour) / time.Second)
	maxAcknowledgementFormSize  = 1024
)

type Handler struct {
	manifest *layout.Manifest
}

func NewHandler(manifest *layout.Manifest) *Handler {
	return &Handler{manifest: manifest}
}

func (h *Handler) RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET "+aurPath, h.index)
	mux.HandleFunc("GET "+aurPath+"/packages/{pkgname}", h.packageRedirect)
	mux.HandleFunc("POST "+aurPath+"/acknowledge", h.acknowledge)
}

func (h *Handler) index(w http.ResponseWriter, r *http.Request) {
	h.showWarning(w, r, "")
}

func (h *Handler) packageRedirect(w http.ResponseWriter, r *http.Request) {
	pkgName := r.PathValue("pkgname")
	if !packageNameRE.MatchString(pkgName) {
		http.NotFound(w, r)
		return
	}

	target := "/packages/" + pkgName
	h.showWarning(w, r, target)
}

func (h *Handler) showWarning(w http.ResponseWriter, r *http.Request, target string) {
	w.Header().Set("Cache-Control", "private, no-store")
	if cookie, err := r.Cookie(acknowledgementCookie); err == nil && cookie.Value == acknowledgementCookieValue {
		redirectToAUR(w, r, target, http.StatusFound)
		return
	}

	page := layout.Page{
		Title:       "AUR: Sicherheitshinweise",
		Description: "Wichtige Hinweise zur sicheren Nutzung des Arch User Repository.",
		Path:        aurPath,
		Manifest:    h.manifest,
	}
	layout.Render(w, r, page, AURPage(target, packageSearchURL(target)))
}

func (h *Handler) acknowledge(w http.ResponseWriter, r *http.Request) {
	w.Header().Set("Cache-Control", "private, no-store")
	r.Body = http.MaxBytesReader(w, r.Body, maxAcknowledgementFormSize)
	if err := r.ParseForm(); err != nil {
		http.Error(w, "invalid form data", http.StatusBadRequest)
		return
	}

	if r.Header.Get("Origin") != layout.GetBaseURL(r) {
		http.Error(w, "invalid origin", http.StatusForbidden)
		return
	}

	target := r.PostForm.Get("target")
	if target != "" && !isPackageTarget(target) {
		http.Error(w, "invalid target", http.StatusBadRequest)
		return
	}

	if r.PostForm.Get("skip-warning") == acknowledgementCookieValue {
		http.SetCookie(w, &http.Cookie{
			Name:     acknowledgementCookie,
			Value:    acknowledgementCookieValue,
			Path:     aurPath,
			MaxAge:   acknowledgementCookieMaxAge,
			Secure:   true,
			HttpOnly: true,
			SameSite: http.SameSiteLaxMode,
		})
	}

	redirectToAUR(w, r, target, http.StatusSeeOther)
}

func isPackageTarget(target string) bool {
	pkgName, found := strings.CutPrefix(target, "/packages/")
	return found && packageNameRE.MatchString(pkgName)
}

func packageSearchURL(target string) string {
	pkgName, found := strings.CutPrefix(target, "/packages/")
	if !found {
		return "/packages"
	}

	return "/packages?search=" + url.QueryEscape(pkgName)
}

func redirectToAUR(w http.ResponseWriter, r *http.Request, target string, status int) {
	// #nosec G710 -- target is either empty or a validated AUR package path
	http.Redirect(w, r, aurURL+strings.TrimPrefix(target, "/"), status)
}
