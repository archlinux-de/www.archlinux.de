package aur

import (
	"net/http"
	"net/http/httptest"
	"net/url"
	"strings"
	"testing"

	"archded/internal/ui/layout"
)

func testManifest() *layout.Manifest {
	m, _ := layout.NewManifest([]byte(`{}`))
	return m
}

func TestIndex(t *testing.T) {
	handler := NewHandler(testManifest())
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/aur", nil))

	if rr.Code != http.StatusOK {
		t.Errorf("expected 200, got %d", rr.Code)
	}
	if cacheControl := rr.Header().Get("Cache-Control"); cacheControl != "private, no-store" {
		t.Errorf("Cache-Control = %q, want private, no-store", cacheControl)
	}
}

func TestAcknowledge(t *testing.T) {
	handler := NewHandler(testManifest())
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	form := url.Values{"skip-warning": {"true"}}
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodPost, "/aur/acknowledge", strings.NewReader(form.Encode()))
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	req.Header.Set("Origin", "http://example.com")
	mux.ServeHTTP(rr, req)

	if rr.Code != http.StatusSeeOther {
		t.Errorf("expected 303, got %d", rr.Code)
	}
	if location := rr.Header().Get("Location"); location != aurURL {
		t.Errorf("Location = %q, want %q", location, aurURL)
	}
	cookies := rr.Result().Cookies()
	if len(cookies) != 1 {
		t.Fatalf("expected one cookie, got %d", len(cookies))
	}
	cookie := cookies[0]
	if cookie.Name != acknowledgementCookie || cookie.Value != "true" || cookie.Path != "/aur" {
		t.Errorf("unexpected cookie: %#v", cookie)
	}
	if !cookie.Secure || !cookie.HttpOnly || cookie.SameSite != http.SameSiteLaxMode {
		t.Errorf("cookie security attributes are incomplete: %#v", cookie)
	}

	rr = httptest.NewRecorder()
	req = httptest.NewRequest(http.MethodGet, "/aur", nil)
	req.AddCookie(cookie)
	mux.ServeHTTP(rr, req)
	if rr.Code != http.StatusFound {
		t.Errorf("expected 302, got %d", rr.Code)
	}
	if location := rr.Header().Get("Location"); location != aurURL {
		t.Errorf("Location = %q, want %q", location, aurURL)
	}
}

func TestAcknowledgeWithoutSkippingWarning(t *testing.T) {
	handler := NewHandler(testManifest())
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodPost, "/aur/acknowledge", nil)
	req.Header.Set("Origin", "http://example.com")
	mux.ServeHTTP(rr, req)

	if rr.Code != http.StatusSeeOther {
		t.Errorf("expected 303, got %d", rr.Code)
	}
	if cookies := rr.Result().Cookies(); len(cookies) != 0 {
		t.Errorf("expected no cookies, got %#v", cookies)
	}
}

func TestPackageRedirect(t *testing.T) {
	handler := NewHandler(testManifest())
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	for _, pkgName := range []string{"foo", "foo.bar+qux@v1", strings.Repeat("a", 256)} {
		t.Run(pkgName, func(t *testing.T) {
			rr := httptest.NewRecorder()
			mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/aur/packages/"+pkgName, nil))

			if rr.Code != http.StatusOK {
				t.Errorf("status = %d, want %d", rr.Code, http.StatusOK)
			}
			if body := rr.Body.String(); !strings.Contains(body, `name="target" value="/packages/`+pkgName+`"`) {
				t.Errorf("response does not contain package target: %q", body)
			}
			if body := rr.Body.String(); !strings.Contains(body, `href="/packages?search=`+url.QueryEscape(pkgName)+`"`) {
				t.Errorf("response does not contain package search URL: %q", body)
			}
		})
	}
}

func TestAcknowledgePackageRedirect(t *testing.T) {
	handler := NewHandler(testManifest())
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	form := url.Values{"skip-warning": {"true"}, "target": {"/packages/foo"}}
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodPost, "/aur/acknowledge", strings.NewReader(form.Encode()))
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	req.Header.Set("Origin", "http://example.com")
	mux.ServeHTTP(rr, req)

	if rr.Code != http.StatusSeeOther {
		t.Errorf("status = %d, want %d", rr.Code, http.StatusSeeOther)
	}
	if location := rr.Header().Get("Location"); location != aurURL+"packages/foo" {
		t.Errorf("Location = %q, want %q", location, aurURL+"packages/foo")
	}
	cookie := rr.Result().Cookies()[0]

	rr = httptest.NewRecorder()
	req = httptest.NewRequest(http.MethodGet, "/aur/packages/foo", nil)
	req.AddCookie(cookie)
	mux.ServeHTTP(rr, req)
	if rr.Code != http.StatusFound {
		t.Errorf("status = %d, want %d", rr.Code, http.StatusFound)
	}
	if location := rr.Header().Get("Location"); location != aurURL+"packages/foo" {
		t.Errorf("Location = %q, want %q", location, aurURL+"packages/foo")
	}
}

func TestAcknowledgeRejectsInvalidTarget(t *testing.T) {
	handler := NewHandler(testManifest())
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	form := url.Values{"skip-warning": {"true"}, "target": {"https://example.com"}}
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodPost, "/aur/acknowledge", strings.NewReader(form.Encode()))
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	req.Header.Set("Origin", "http://example.com")
	mux.ServeHTTP(rr, req)

	if rr.Code != http.StatusBadRequest {
		t.Errorf("status = %d, want %d", rr.Code, http.StatusBadRequest)
	}
	if cookies := rr.Result().Cookies(); len(cookies) != 0 {
		t.Errorf("expected no cookies, got %#v", cookies)
	}
}

func TestAcknowledgeRejectsCrossOriginRequest(t *testing.T) {
	handler := NewHandler(testManifest())
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	form := url.Values{"skip-warning": {"true"}}
	rr := httptest.NewRecorder()
	req := httptest.NewRequest(http.MethodPost, "/aur/acknowledge", strings.NewReader(form.Encode()))
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	req.Header.Set("Origin", "https://example.com")
	mux.ServeHTTP(rr, req)

	if rr.Code != http.StatusForbidden {
		t.Errorf("status = %d, want %d", rr.Code, http.StatusForbidden)
	}
	if cookies := rr.Result().Cookies(); len(cookies) != 0 {
		t.Errorf("expected no cookies, got %#v", cookies)
	}
}

func TestPackageRedirectRejectsInvalidNames(t *testing.T) {
	handler := NewHandler(testManifest())
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	for _, pkgName := range []string{".foo", "foo!", strings.Repeat("a", 257)} {
		t.Run(pkgName, func(t *testing.T) {
			rr := httptest.NewRecorder()
			mux.ServeHTTP(rr, httptest.NewRequest(http.MethodGet, "/aur/packages/"+pkgName, nil))

			if rr.Code != http.StatusNotFound {
				t.Errorf("status = %d, want %d", rr.Code, http.StatusNotFound)
			}
		})
	}
}

func TestAcknowledgeRejectsOversizedForm(t *testing.T) {
	handler := NewHandler(testManifest())
	mux := http.NewServeMux()
	handler.RegisterRoutes(mux)

	rr := httptest.NewRecorder()
	req := httptest.NewRequest(
		http.MethodPost,
		"/aur/acknowledge",
		strings.NewReader(strings.Repeat("x", maxAcknowledgementFormSize+1)),
	)
	req.Header.Set("Content-Type", "application/x-www-form-urlencoded")
	mux.ServeHTTP(rr, req)

	if rr.Code != http.StatusBadRequest {
		t.Errorf("expected 400, got %d", rr.Code)
	}
}
