package layout

import (
	"net/http"
	"strconv"
)

const (
	MaxSearchLen = 255
	MaxOffset    = 10000
)

func ParseSearchParams(r *http.Request) (search string, offset int) {
	search = r.URL.Query().Get("search")
	if len(search) > MaxSearchLen {
		search = search[:MaxSearchLen]
	}
	offset, _ = strconv.Atoi(r.URL.Query().Get("offset"))
	if offset < 0 || offset > MaxOffset {
		offset = 0
	}
	return search, offset
}

type Pagination struct {
	Total  int
	Offset int
	Limit  int
}

func (p Pagination) HasPrevious() bool { return p.Offset > 0 }
func (p Pagination) HasNext() bool     { return p.Offset+p.Limit < p.Total }
func (p Pagination) From() int         { return p.Offset + 1 }

func (p Pagination) To() int {
	to := p.Offset + p.Limit
	if to > p.Total {
		to = p.Total
	}
	return to
}

func (p Pagination) PrevOffset() int {
	o := p.Offset - p.Limit
	if o < 0 {
		o = 0
	}
	return o
}

func (p Pagination) NextOffset() int {
	return p.Offset + p.Limit
}
