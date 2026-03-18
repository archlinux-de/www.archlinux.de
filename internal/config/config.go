package config

import (
	"errors"
	"os"
)

type Config struct {
	Database       string
	GeoIPDatabase  string
	Port           string
	PackagesMirror string
}

func Load() (Config, error) {
	cfg := Config{
		Database:       getEnv("DATABASE", ""),
		GeoIPDatabase:  getEnv("GEOIP_DATABASE", ""),
		Port:           getEnv("PORT", "8080"),
		PackagesMirror: getEnv("PACKAGES_MIRROR", "https://geo.mirror.pkgbuild.com/"),
	}

	if cfg.Database == "" {
		return Config{}, errors.New("DATABASE environment variable is required")
	}

	return cfg, nil
}

func getEnv(key, defaultValue string) string {
	if value := os.Getenv(key); value != "" {
		return value
	}
	return defaultValue
}
