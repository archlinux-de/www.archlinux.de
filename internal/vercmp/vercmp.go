// Package vercmp implements libalpm version comparison.
// Ported from https://gitlab.archlinux.org/archlinux/alpm/pacman/-/blob/master/lib/libalpm/version.c
package vercmp

import "strings"

// Vercmp compares two version strings as pacman/libalpm does.
// Returns -1 if a is older, 0 if equal, 1 if a is newer.
func Vercmp(a, b string) int {
	if a == b {
		return 0
	}

	epoch1, ver1, rel1 := parseEVR(a)
	epoch2, ver2, rel2 := parseEVR(b)

	ret := rpmvercmp(epoch1, epoch2)
	if ret == 0 {
		ret = rpmvercmp(ver1, ver2)
		if ret == 0 && rel1 != "" && rel2 != "" {
			ret = rpmvercmp(rel1, rel2)
		}
	}
	return ret
}

// parseEVR splits "[epoch:]version[-release]" into its components.
func parseEVR(evr string) (epoch, version, release string) {
	epoch = "0"

	// Find epoch: scan leading digits, then check for ':'
	i := 0
	for i < len(evr) && isDigit(evr[i]) {
		i++
	}
	if i < len(evr) && evr[i] == ':' {
		epoch = evr[:i]
		if epoch == "" {
			epoch = "0"
		}
		evr = evr[i+1:]
	}

	// Find release: last '-' separates version from release
	if idx := strings.LastIndex(evr, "-"); idx >= 0 {
		version = evr[:idx]
		release = evr[idx+1:]
	} else {
		version = evr
	}

	return epoch, version, release
}

// rpmvercmp compares alpha and numeric segments of two version strings.
//
//nolint:gocyclo // mirrors pacman's alpm_pkg_vercmp algorithm
func rpmvercmp(a, b string) int {
	if a == b {
		return 0
	}

	ia, ib := 0, 0

	for ia < len(a) && ib < len(b) {
		// Skip non-alphanumeric characters
		pia, pib := ia, ib
		for ia < len(a) && !isAlnum(a[ia]) {
			ia++
		}
		for ib < len(b) && !isAlnum(b[ib]) {
			ib++
		}

		if ia >= len(a) || ib >= len(b) {
			break
		}

		// If separator lengths differ, the shorter one wins
		if (ia - pia) != (ib - pib) {
			if (ia - pia) < (ib - pib) {
				return -1
			}
			return 1
		}

		// Grab a completely numeric or completely alpha segment
		ja, jb := ia, ib
		isnum := isDigit(a[ia])
		if isnum {
			for ja < len(a) && isDigit(a[ja]) {
				ja++
			}
			for jb < len(b) && isDigit(b[jb]) {
				jb++
			}
		} else {
			for ja < len(a) && isAlpha(a[ja]) {
				ja++
			}
			for jb < len(b) && isAlpha(b[jb]) {
				jb++
			}
		}

		seg1 := a[ia:ja]
		seg2 := b[ib:jb]

		// Empty first segment shouldn't happen, but handle it
		if len(seg1) == 0 {
			return -1
		}

		// Different types: one numeric, the other alpha (empty)
		if len(seg2) == 0 {
			if isnum {
				return 1
			}
			return -1
		}

		if isnum {
			// Strip leading zeros and compare by length first
			s1 := strings.TrimLeft(seg1, "0")
			s2 := strings.TrimLeft(seg2, "0")

			if len(s1) > len(s2) {
				return 1
			}
			if len(s2) > len(s1) {
				return -1
			}

			// Same length: compare lexicographically (works for digits)
			if s1 > s2 {
				return 1
			}
			if s1 < s2 {
				return -1
			}
		} else {
			if seg1 > seg2 {
				return 1
			}
			if seg1 < seg2 {
				return -1
			}
		}

		ia = ja
		ib = jb
	}

	// Both exhausted
	if ia >= len(a) && ib >= len(b) {
		return 0
	}

	// One has remaining characters
	if ia >= len(a) {
		if ib < len(b) && !isAlpha(b[ib]) {
			return -1
		}
		return -1
	}
	if isAlpha(a[ia]) {
		return -1
	}
	return 1
}

func isDigit(c byte) bool { return c >= '0' && c <= '9' }
func isAlpha(c byte) bool { return (c >= 'a' && c <= 'z') || (c >= 'A' && c <= 'Z') }
func isAlnum(c byte) bool { return isDigit(c) || isAlpha(c) }
