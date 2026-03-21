package opensearch

import (
	"net/http"
	"net/http/httptest"
	"strings"
	"testing"
)

func TestHandler(t *testing.T) {
	mux := http.NewServeMux()
	RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/packages/opensearch", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}

	ct := rr.Header().Get("Content-Type")
	if !strings.Contains(ct, "application/opensearchdescription+xml") {
		t.Errorf("expected opensearch content-type, got %q", ct)
	}

	body := rr.Body.String()
	if !strings.Contains(body, "Paket-Suche") {
		t.Error("expected body to contain ShortName")
	}
	if !strings.Contains(body, "{searchTerms}") {
		t.Error("expected body to contain search template")
	}
	if !strings.Contains(body, "<?xml") {
		t.Error("expected XML header")
	}
}
