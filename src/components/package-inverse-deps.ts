const labels: Record<string, string> = {
    depends: "Benötigt von",
    optdepends: "Optional für",
    makedepends: "Build-Abhängigkeit von",
    checkdepends: "Test-Abhängigkeit von",
};

class PackageInverseDeps extends HTMLElement {
    connectedCallback() {
        const src = this.getAttribute("src");
        const arch = this.getAttribute("arch");
        if (!src || !arch) {
            return;
        }

        const button = document.createElement("button");
        button.className = "btn btn-outline-primary btn-sm ms-4";
        button.textContent = "Inverse Abhängigkeiten anzeigen";
        this.appendChild(button);

        button.addEventListener(
            "click",
            async () => {
                button.textContent = "Laden…";
                button.disabled = true;

                const response = await fetch(src);
                const data: Record<string, string[]> = await response.json();

                button.remove();

                const fragment = document.createDocumentFragment();
                let hasContent = false;

                for (const [type, names] of Object.entries(data)) {
                    if (!names || names.length === 0) {
                        continue;
                    }
                    hasContent = true;

                    const h3 = document.createElement("h3");
                    h3.textContent = labels[type] ?? type;
                    fragment.appendChild(h3);

                    const div = document.createElement("div");
                    div.className = "d-flex flex-wrap gap-2 ps-4 mb-3";

                    for (const name of names) {
                        const a = document.createElement("a");
                        a.href = `/packages/${arch}/${name}`;
                        a.textContent = name;
                        div.appendChild(a);
                    }

                    fragment.appendChild(div);
                }

                if (!hasContent) {
                    const p = document.createElement("p");
                    p.className = "text-muted";
                    p.textContent = "Keine inversen Abhängigkeiten gefunden.";
                    fragment.appendChild(p);
                }

                this.appendChild(fragment);
            },
            { once: true },
        );
    }
}

customElements.define("package-inverse-deps", PackageInverseDeps);
