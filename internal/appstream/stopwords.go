package appstream

// stopword is a small English + German closed-class word set (articles,
// conjunctions, common prepositions, auxiliaries, pronouns). It trims noise for
// FTS without pulling in NLP dependencies. Extend deliberately: short words like
// "go" or "c" are omitted because they double as names.
var stopword map[string]struct{}

func init() {
	words := []string{
		// English
		"a", "about", "after", "again", "all", "am", "an", "and", "any", "are", "as", "at",
		"be", "been", "before", "being", "between", "both", "but", "by",
		"can", "could",
		"did", "do", "does", "doing", "done", "during",
		"each", "few", "for", "from", "further",
		"had", "has", "have", "having", "he", "her", "here", "hers", "herself", "him", "himself", "his", "how",
		"i", "if", "in", "into", "is", "it", "its", "itself",
		"just",
		"me", "more", "most", "my", "myself",
		"no", "nor", "not",
		"of", "off", "on", "once", "only", "or", "other", "our", "ours", "ourselves", "out", "over", "own",
		"same", "she", "should", "so", "some", "such",
		"than", "that", "the", "their", "theirs", "them", "themselves", "then", "there", "these", "they", "this", "those", "through", "to", "too",
		"under", "until", "up",
		"very",
		"was", "we", "were", "what", "when", "where", "which", "while", "who", "whom", "why", "will", "with", "would",
		"you", "your", "yours", "yourself", "yourselves",
		// German
		"als", "am", "an", "auch", "auf", "aus", "bei", "bin", "bis", "bist", "da", "das", "dass", "dein", "deine",
		"dem", "den", "der", "des", "dich", "die", "dir", "doch", "du", "durch", "ein", "eine", "einem", "einen", "einer",
		"eines", "er", "es", "euch", "euer", "eure", "für", "hab", "habe", "haben", "hast", "hat", "hatte", "hatten", "hattest",
		"hattet", "hier", "ich", "ihm", "ihn", "ihr", "ihre", "ihrem", "ihren", "ihrer", "ihres", "im", "in", "ist", "ja", "jede",
		"jedem", "jeden", "jeder", "jedes", "kann", "kannst", "können", "könnt", "machen", "man", "mein", "meine", "mich", "mir",
		"mit", "muss", "musst", "nach", "nicht", "noch", "nun", "nur", "ob", "oder", "ohne", "seid", "sein", "seine", "seinem",
		"seinen", "seiner", "seines", "sich", "sie", "sind", "so", "soll", "sollen", "sollst", "sollt", "sonst", "sowie", "um",
		"und", "uns", "unser", "unsere", "unter", "vom", "von", "vor", "war", "waren", "warst", "wart", "was", "weg", "weil",
		"weiter", "welche", "welchem", "welchen", "welcher", "welches", "wenn", "wer", "werde", "werden", "werdet", "wie",
		"wieder", "will", "wir", "wird", "wirst", "wo", "wohin", "wollen", "wollt", "würde", "würden", "zu", "zum", "zur", "über",
	}
	stopword = make(map[string]struct{}, len(words))
	for _, w := range words {
		stopword[w] = struct{}{}
	}
}
