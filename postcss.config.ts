import purgeCSSPlugin from "@fullhuman/postcss-purgecss";
import cssnano from "cssnano";

export default {
    plugins: [
        purgeCSSPlugin({
            content: ["**/*.templ", "**/*.ts"],
            skippedContentGlobs: ["node_modules/**", "tests/**", "tmp/**"],
            variables: true,
        }),
        cssnano({ preset: ["cssnano-preset-advanced"] }),
    ],
};
