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

        button.addEventListener(
            "click",
            async () => {
                button.remove();

                const response = await fetch(src);
                const files: string[] = await response.json();

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
            },
            { once: true },
        );
    }
}

customElements.define("package-files", PackageFiles);
