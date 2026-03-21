package opensearch

import (
	"encoding/xml"
	"net/http"

	"archded/internal/ui/layout"
)

func RegisterRoutes(mux *http.ServeMux) {
	mux.HandleFunc("GET /packages/opensearch", handler)
}

type openSearchDescription struct {
	XMLName     xml.Name `xml:"OpenSearchDescription"`
	XMLNS       string   `xml:"xmlns,attr"`
	ShortName   string   `xml:"ShortName"`
	Description string   `xml:"Description"`
	Image       osImage  `xml:"Image"`
	URLs        []osURL  `xml:"Url"`
}

type osImage struct {
	Type    string `xml:"type,attr"`
	Content string `xml:",chardata"`
}

type osURL struct {
	Type     string `xml:"type,attr"`
	Rel      string `xml:"rel,attr,omitempty"`
	Template string `xml:"template,attr"`
}

func handler(w http.ResponseWriter, r *http.Request) {
	baseURL := layout.GetBaseURL(r)
	desc := openSearchDescription{
		XMLNS:       "http://a9.com/-/spec/opensearch/1.1/",
		ShortName:   "Paket-Suche",
		Description: "Suche nach Arch Linux Paketen",
		Image:       osImage{Type: "image/svg+xml", Content: baseURL + "/static/archicon.svg"},
		URLs: []osURL{
			{Type: "text/html", Template: baseURL + "/packages?search={searchTerms}"},
			{Type: "application/opensearchdescription+xml", Rel: "self", Template: baseURL + "/packages/opensearch"},
		},
	}

	w.Header().Set("Content-Type", "application/opensearchdescription+xml; charset=UTF-8")
	_, _ = w.Write([]byte(xml.Header))
	enc := xml.NewEncoder(w)
	enc.Indent("", "  ")
	_ = enc.Encode(desc)
}
