package news

import "testing"

func TestSlug(t *testing.T) {
	tests := []struct {
		title string
		want  string
	}{
		{"Breaking News", "Breaking-News"},
		{"Big Story", "Big-Story"},
		{"Das Canterbury Projekt", "Das-Canterbury-Projekt"},
		{"Änderung der Paketstruktur", "Aenderung-der-Paketstruktur"},
		{"Große Überraschung", "Grosse-Ueberraschung"},
		{"hello---world", "hello-world"},
		{"  leading and trailing  ", "leading-and-trailing"},
		{"", ""},
		{"simple", "simple"},
		{"Arch Linux 2024.01.01", "Arch-Linux-2024-01-01"},
		{"foo/bar baz", "foo-bar-baz"},
	}

	for _, tt := range tests {
		t.Run(tt.title, func(t *testing.T) {
			got := slug(tt.title)
			if got != tt.want {
				t.Errorf("slug(%q) = %q, want %q", tt.title, got, tt.want)
			}
		})
	}
}

func TestNewsURL(t *testing.T) {
	tests := []struct {
		id    int
		title string
		want  string
	}{
		{1, "Breaking News", "/news/1-Breaking-News"},
		{18784, "Das Canterbury Projekt", "/news/18784-Das-Canterbury-Projekt"},
		{42, "", "/news/42"},
	}

	for _, tt := range tests {
		t.Run(tt.want, func(t *testing.T) {
			got := newsURL(tt.id, tt.title)
			if got != tt.want {
				t.Errorf("newsURL(%d, %q) = %q, want %q", tt.id, tt.title, got, tt.want)
			}
		})
	}
}
