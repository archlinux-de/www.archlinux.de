package layout

import "testing"

func TestFormatSize(t *testing.T) {
	tests := []struct {
		input int64
		want  string
	}{
		{0, "0 B"},
		{500, "500 B"},
		{999, "999 B"},
		{1000, "1 kB"},
		{1500, "1 kB"},
		{999999, "999 kB"},
		{1000000, "1 MB"},
		{1500000, "1 MB"},
		{1000000000, "1 GB"},
		{1500000000, "1 GB"},
	}
	for _, tt := range tests {
		got := FormatSize(tt.input)
		if got != tt.want {
			t.Errorf("FormatSize(%d) = %q, want %q", tt.input, got, tt.want)
		}
	}
}

func TestFormatFileSize(t *testing.T) {
	tests := []struct {
		input int64
		want  string
	}{
		{0, ""},
		{-1, ""},
		{500, "500 B"},
		{1024, "1,0 KB"},
		{1536, "1,5 KB"},
		{1048576, "1,0 MB"},
		{1073741824, "1,0 GB"},
		{1522106368, "1,4 GB"},
	}
	for _, tt := range tests {
		got := FormatFileSize(tt.input)
		if got != tt.want {
			t.Errorf("FormatFileSize(%d) = %q, want %q", tt.input, got, tt.want)
		}
	}
}

func TestFormatDate(t *testing.T) {
	tests := []struct {
		input int64
		want  string
	}{
		{0, ""},
		{1704067200, "01.01.2024"},
	}
	for _, tt := range tests {
		got := FormatDate(tt.input)
		if got != tt.want {
			t.Errorf("FormatDate(%d) = %q, want %q", tt.input, got, tt.want)
		}
	}
}
