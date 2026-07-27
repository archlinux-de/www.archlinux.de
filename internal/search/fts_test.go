package search

import "testing"

func TestFTSQuery(t *testing.T) {
	tests := []struct {
		input, want string
	}{
		{"linux", `"linux"*`},
		{"lib-json", `"lib" "json"*`},
		{`pkg"name`, `"pkg""name"*`},
		{"", `""`},
		{"  ", `""`},
	}
	for _, tt := range tests {
		got := FTSQuery(tt.input)
		if got != tt.want {
			t.Errorf("FTSQuery(%q) = %q, want %q", tt.input, got, tt.want)
		}
	}
}

func TestFallbackFTSQuery(t *testing.T) {
	tests := []struct {
		input, want string
	}{
		{"php-git", `"php"*`},
		{"php", ""},
		{"-php", ""},
		{"php-", ""},
	}
	for _, tt := range tests {
		got := FallbackFTSQuery(tt.input)
		if got != tt.want {
			t.Errorf("FallbackFTSQuery(%q) = %q, want %q", tt.input, got, tt.want)
		}
	}
}
