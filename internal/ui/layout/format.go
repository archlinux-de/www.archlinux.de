package layout

import "fmt"

// FormatSize formats a byte count using SI units (1000-based).
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
