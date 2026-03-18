package news

import (
	"fmt"
	"strings"
	"unicode"
)

var transliterations = map[rune]string{
	'ä': "ae", 'ö': "oe", 'ü': "ue", 'ß': "ss",
	'Ä': "Ae", 'Ö': "Oe", 'Ü': "Ue",
	'à': "a", 'á': "a", 'â': "a", 'ã': "a",
	'è': "e", 'é': "e", 'ê': "e", 'ë': "e",
	'ì': "i", 'í': "i", 'î': "i", 'ï': "i",
	'ò': "o", 'ó': "o", 'ô': "o", 'õ': "o",
	'ù': "u", 'ú': "u", 'û': "u",
	'ñ': "n", 'ç': "c",
	'À': "A", 'Á': "A", 'Â': "A", 'Ã': "A",
	'È': "E", 'É': "E", 'Ê': "E", 'Ë': "E",
	'Ì': "I", 'Í': "I", 'Î': "I", 'Ï': "I",
	'Ò': "O", 'Ó': "O", 'Ô': "O", 'Õ': "O",
	'Ù': "U", 'Ú': "U", 'Û': "U",
	'Ñ': "N", 'Ç': "C",
}

func slug(title string) string {
	var b strings.Builder
	b.Grow(len(title))

	for _, r := range title {
		if repl, ok := transliterations[r]; ok {
			b.WriteString(repl)
		} else if unicode.IsLetter(r) || unicode.IsDigit(r) {
			b.WriteRune(r)
		} else {
			b.WriteByte('-')
		}
	}

	s := b.String()

	// collapse multiple hyphens
	for strings.Contains(s, "--") {
		s = strings.ReplaceAll(s, "--", "-")
	}

	return strings.Trim(s, "-")
}

func newsURL(id int, title string) string {
	s := slug(title)
	if s == "" {
		return fmt.Sprintf("/news/%d", id)
	}
	return fmt.Sprintf("/news/%d-%s", id, s)
}
