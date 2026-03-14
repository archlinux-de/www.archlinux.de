package vercmp

import "testing"

func TestVercmp(t *testing.T) {
	// Test cases from https://gitlab.archlinux.org/pacman/pacman/-/blob/master/test/util/vercmptest.sh
	tests := []struct {
		a, b string
		want int
	}{
		// all similar length, no pkgrel
		{"1.5.0", "1.5.0", 0},
		{"1.5.1", "1.5.0", 1},

		// mixed length
		{"1.5.1", "1.5", 1},

		// with pkgrel, simple
		{"1.5.0-1", "1.5.0-1", 0},
		{"1.5.0-1", "1.5.0-2", -1},
		{"1.5.0-1", "1.5.1-1", -1},
		{"1.5.0-2", "1.5.1-1", -1},

		// with pkgrel, mixed lengths
		{"1.5-1", "1.5.1-1", -1},
		{"1.5-2", "1.5.1-1", -1},
		{"1.5-2", "1.5.1-2", -1},

		// mixed pkgrel inclusion
		{"1.5", "1.5-1", 0},
		{"1.5-1", "1.5", 0},
		{"1.1-1", "1.1", 0},
		{"1.0-1", "1.1", -1},
		{"1.1-1", "1.0", 1},

		// alphanumeric versions
		{"1.5b-1", "1.5-1", -1},
		{"1.5b", "1.5", -1},
		{"1.5b-1", "1.5", -1},
		{"1.5b", "1.5.1", -1},

		// from the manpage
		{"1.0a", "1.0alpha", -1},
		{"1.0alpha", "1.0b", -1},
		{"1.0b", "1.0beta", -1},
		{"1.0beta", "1.0rc", -1},
		{"1.0rc", "1.0", -1},

		// going crazy? alpha-dotted versions
		{"1.5.a", "1.5", 1},
		{"1.5.b", "1.5.a", 1},
		{"1.5.1", "1.5.b", 1},

		// alpha dots and dashes
		{"1.5.b-1", "1.5.b", 0},
		{"1.5-1", "1.5.b", -1},

		// same/similar content, differing separators
		{"2.0", "2_0", 0},
		{"2.0_a", "2_0.a", 0},
		{"2.0a", "2.0.a", -1},
		{"2___a", "2_a", 1},

		// epoch included version comparisons
		{"0:1.0", "0:1.0", 0},
		{"0:1.0", "0:1.1", -1},
		{"1:1.0", "0:1.0", 1},
		{"1:1.0", "0:1.1", 1},
		{"1:1.0", "2:1.1", -1},

		// epoch + sometimes present pkgrel
		{"1:1.0", "0:1.0-1", 1},
		{"1:1.0-1", "0:1.1-1", 1},

		// epoch included on one version
		{"0:1.0", "1.0", 0},
		{"0:1.0", "1.1", -1},
		{"0:1.1", "1.0", 1},
		{"1:1.0", "1.0", 1},
		{"1:1.0", "1.1", 1},
		{"1:1.1", "1.1", 1},
	}

	for _, tt := range tests {
		got := Vercmp(tt.a, tt.b)
		if got != tt.want {
			t.Errorf("Vercmp(%q, %q) = %d, want %d", tt.a, tt.b, got, tt.want)
		}
	}
}

func TestVercmpSymmetry(t *testing.T) {
	pairs := [][2]string{
		{"1.0", "2.0"},
		{"1.5.0-1", "1.5.0-2"},
		{"1:1.0", "0:2.0"},
		{"1.0a", "1.0b"},
	}
	for _, p := range pairs {
		ab := Vercmp(p[0], p[1])
		ba := Vercmp(p[1], p[0])
		if ab != -ba {
			t.Errorf("Vercmp(%q,%q)=%d but Vercmp(%q,%q)=%d (not symmetric)", p[0], p[1], ab, p[1], p[0], ba)
		}
	}
}

func TestVercmpIdentity(t *testing.T) {
	versions := []string{"1.0", "1.0-1", "1:1.0-1", "0:1.0", "1.0alpha"}
	for _, v := range versions {
		if got := Vercmp(v, v); got != 0 {
			t.Errorf("Vercmp(%q, %q) = %d, want 0", v, v, got)
		}
	}
}

func TestParseEVR(t *testing.T) {
	tests := []struct {
		input                   string
		epoch, version, release string
	}{
		{"1.0", "0", "1.0", ""},
		{"1.0-1", "0", "1.0", "1"},
		{"1:1.0-1", "1", "1.0", "1"},
		{"0:1.0", "0", "1.0", ""},
		{"12:3.4.5-6", "12", "3.4.5", "6"},
	}
	for _, tt := range tests {
		e, v, r := parseEVR(tt.input)
		if e != tt.epoch || v != tt.version || r != tt.release {
			t.Errorf("parseEVR(%q) = (%q, %q, %q), want (%q, %q, %q)",
				tt.input, e, v, r, tt.epoch, tt.version, tt.release)
		}
	}
}
