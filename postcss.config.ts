import purgeCSSPlugin from "@fullhuman/postcss-purgecss";
import cssnano from "cssnano";

export default {
    plugins: [
        purgeCSSPlugin({
            content: ["**/*.templ", "**/*.ts"],
            skippedContentGlobs: ["node_modules/**", "tests/**", "tmp/**"],
            variables: true,
            safelist: {
                // keep rules for classes only present in go:embed SVGs
                greedy: [/package-popularity/],
            },
        }),
        cssnano({ preset: ["cssnano-preset-advanced"] }),
    ],
};
