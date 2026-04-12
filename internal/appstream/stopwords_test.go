package appstream

import "testing"

func TestDedupeWords_Stopwords(t *testing.T) {
	got := dedupeWords([]string{"The cat and the dog in a box"})
	want := "cat dog box"
	if got != want {
		t.Fatalf("got %q want %q", got, want)
	}
}

func TestDedupeWords_GermanStopwords(t *testing.T) {
	got := dedupeWords([]string{"der schnelle braune Fuchs"})
	want := "schnelle braune Fuchs"
	if got != want {
		t.Fatalf("got %q want %q", got, want)
	}
}
