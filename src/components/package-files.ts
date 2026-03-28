class PackageFiles extends HTMLElement {
    connectedCallback() {
        const src = this.getAttribute("src");
        if (!src) {
            return;
        }

        const button = document.createElement("button");
        button.className = "btn btn-outline-primary btn-sm ms-4";
        button.textContent = "Dateien anzeigen";
        this.appendChild(button);

        button.addEventListener("click", async () => {
            button.textContent = "Laden…";
            button.disabled = true;

            try {
                const response = await fetch(src);
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }
                const files: string[] = await response.json();

                button.remove();

                const ul = document.createElement("ul");
                ul.className = "list-unstyled ms-4 overflow-auto";

                for (const file of files) {
                    const li = document.createElement("li");
                    li.textContent = file;
                    if (file.endsWith("/")) {
                        li.className = "text-muted";
                    }
                    ul.appendChild(li);
                }

                this.appendChild(ul);
            } catch {
                button.textContent = "Fehler beim Laden";
                button.disabled = false;
            }
        });
    }
}

customElements.define("package-files", PackageFiles);
