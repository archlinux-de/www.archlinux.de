package ui

import (
	"io/fs"
	"net/http"
	"net/http/httptest"
	"testing"
	"testing/fstest"

	"archded/internal/web"
)

func TestAssetAndStaticRootsDoNotRedirectLoop(t *testing.T) {
	tests := []struct {
		name  string
		path  string
		setup func(*http.ServeMux, fs.FS) error
		files fstest.MapFS
	}{
		{
			name:  "assets",
			path:  "/assets",
			setup: handleAssets,
			files: fstest.MapFS{"dist/assets/main.css": {Data: []byte("body {}")}},
		},
		{
			name:  "static",
			path:  "/static",
			setup: handleStatic,
			files: fstest.MapFS{"static/archlogo.svg": {Data: []byte("<svg/>")}},
		},
	}

	for _, tt := range tests {
		t.Run(tt.name, func(t *testing.T) {
			mux := http.NewServeMux()
			if err := tt.setup(mux, tt.files); err != nil {
				t.Fatal(err)
			}
			handler := web.RedirectTrailingSlash()(mux)

			rr := httptest.NewRecorder()
			handler.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, tt.path, nil))
			if rr.Code != http.StatusNotFound {
				t.Errorf("%s status = %d, want %d; redirects to %q", tt.path, rr.Code, http.StatusNotFound, rr.Header().Get("Location"))
			}

			rr = httptest.NewRecorder()
			handler.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, tt.path+"/", nil))
			if rr.Code != http.StatusMovedPermanently || rr.Header().Get("Location") != tt.path {
				t.Errorf("%s/ = %d redirect to %q, want 301 to %q", tt.path, rr.Code, rr.Header().Get("Location"), tt.path)
			}
		})
	}
}
