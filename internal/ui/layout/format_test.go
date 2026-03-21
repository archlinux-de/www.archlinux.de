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
