package layout

import (
	"fmt"
	"strings"
	"time"
)

// FormatSize formats a byte count using SI units (1000-based) for machine-readable contexts.
func FormatSize(b int64) string {
	const (
		kb = 1000
		mb = 1000 * kb
		gb = 1000 * mb
	)
	switch {
	case b >= gb:
		return fmt.Sprintf("%d GB", b/gb)
	case b >= mb:
		return fmt.Sprintf("%d MB", b/mb)
	case b >= kb:
		return fmt.Sprintf("%d kB", b/kb)
	default:
		return fmt.Sprintf("%d B", b)
	}
}

// FormatFileSize formats a byte count using binary units (1024-based) with German decimal separator.
func FormatFileSize(bytes int64) string {
	if bytes <= 0 {
		return ""
	}
	const (
		kb = 1024
		mb = 1024 * kb
		gb = 1024 * mb
	)
	var s string
	switch {
	case bytes >= gb:
		s = fmt.Sprintf("%.1f GB", float64(bytes)/float64(gb))
	case bytes >= mb:
		s = fmt.Sprintf("%.1f MB", float64(bytes)/float64(mb))
	case bytes >= kb:
		s = fmt.Sprintf("%.1f KB", float64(bytes)/float64(kb))
	default:
		return fmt.Sprintf("%d B", bytes)
	}
	return strings.Replace(s, ".", ",", 1)
}

func FormatDate(unix int64) string {
	if unix == 0 {
		return ""
	}
	return time.Unix(unix, 0).Format("02.01.2006")
}
