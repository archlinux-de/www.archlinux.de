import { writeFileSync } from "node:fs";
import { resolve } from "node:path";
import { fileURLToPath, URL } from "node:url";
import type { Plugin, UserConfig } from "vite";

const isWatch = process.argv.includes("--watch");

function notifyAir(): Plugin {
    return {
        name: "notify-air",
        writeBundle() {
            writeFileSync(".assets-rebuilt", String(Date.now()));
        },
    };
}

export default {
    css: {
        preprocessorOptions: {
            scss: {
                quietDeps: true,
                silenceDeprecations: ["import"],
                loadPaths: [
                    resolve(
                        fileURLToPath(new URL(".", import.meta.url)),
                        "node_modules",
                    ),
                ],
            },
        },
    },
    plugins: isWatch ? [notifyAir()] : [],
    publicDir: false,
    build: {
        manifest: "manifest.json",
        minify: !isWatch,
        rolldownOptions: {
            input: "src/main.ts",
        },
    },
} satisfies UserConfig;
