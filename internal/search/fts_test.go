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
