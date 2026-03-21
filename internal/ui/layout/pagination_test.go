package layout

import "testing"

func TestPagination(t *testing.T) {
	p := Pagination{Total: 100, Limit: 25, Offset: 0}
	if !p.HasNext() {
		t.Error("expected HasNext")
	}
	if p.HasPrevious() {
		t.Error("expected no HasPrevious at offset 0")
	}
	if p.From() != 1 {
		t.Errorf("expected From=1, got %d", p.From())
	}
	if p.To() != 25 {
		t.Errorf("expected To=25, got %d", p.To())
	}
	if p.NextOffset() != 25 {
		t.Errorf("expected NextOffset=25, got %d", p.NextOffset())
	}
	if p.PrevOffset() != 0 {
		t.Errorf("expected PrevOffset=0 (clamped), got %d", p.PrevOffset())
	}
}

func TestPagination_MiddlePage(t *testing.T) {
	p := Pagination{Total: 100, Limit: 25, Offset: 50}
	if !p.HasPrevious() {
		t.Error("expected HasPrevious")
	}
	if !p.HasNext() {
		t.Error("expected HasNext")
	}
	if p.PrevOffset() != 25 {
		t.Errorf("expected PrevOffset=25, got %d", p.PrevOffset())
	}
	if p.From() != 51 {
		t.Errorf("expected From=51, got %d", p.From())
	}
	if p.To() != 75 {
		t.Errorf("expected To=75, got %d", p.To())
	}
}

func TestPagination_LastPage(t *testing.T) {
	p := Pagination{Total: 90, Limit: 25, Offset: 75}
	if !p.HasPrevious() {
		t.Error("expected HasPrevious")
	}
	if p.HasNext() {
		t.Error("expected no HasNext on last page")
	}
	if p.To() != 90 {
		t.Errorf("expected To=90 (clamped to total), got %d", p.To())
	}
}

func TestPagination_SinglePage(t *testing.T) {
	p := Pagination{Total: 10, Limit: 25, Offset: 0}
	if p.HasNext() {
		t.Error("expected no HasNext")
	}
	if p.HasPrevious() {
		t.Error("expected no HasPrevious")
	}
	if p.To() != 10 {
		t.Errorf("expected To=10, got %d", p.To())
	}
}

func TestPagination_Empty(t *testing.T) {
	p := Pagination{Total: 0, Limit: 25, Offset: 0}
	if p.HasNext() {
		t.Error("expected no HasNext")
	}
	if p.From() != 1 {
		t.Errorf("expected From=1, got %d", p.From())
	}
	if p.To() != 0 {
		t.Errorf("expected To=0, got %d", p.To())
	}
}
