import purgeCSSPlugin from "@fullhuman/postcss-purgecss";
import cssnano from "cssnano";

export default {
    plugins: [
        purgeCSSPlugin({
            content: ["**/*.templ", "**/*.ts"],
            skippedContentGlobs: ["node_modules/**", "tests/**", "tmp/**"],
            // keep rules for classes only present in go:embed SVGs
            safelist: { greedy: [/package-popularity/] },
            variables: true,
        }),
        cssnano({ preset: ["cssnano-preset-advanced"] }),
    ],
};
