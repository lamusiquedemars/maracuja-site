/**
 * Work around WebKit 26.5+ dropping multipart bodies for disk-backed Files.
 * This is deliberately limited to Livewire's temporary-upload endpoint.
 *
 * A sliced Blob works for many files but is still backed by the original
 * disk file in some Safari builds. Copying its bytes first reliably moves
 * the request onto WebKit's in-memory upload path.
 */
(() => {
    const isWebKit = /AppleWebKit/i.test(navigator.userAgent)
        && !/(Chrome|Chromium|CriOS|Edg)/i.test(navigator.userAgent);

    if (!isWebKit || window.__maracujaSafariLivewireUploadFixInstalled) return;

    window.__maracujaSafariLivewireUploadFixInstalled = true;

    const open = XMLHttpRequest.prototype.open;
    const send = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function (method, url, ...arguments_) {
        this.__maracujaRequestUrl = String(url);

        return open.call(this, method, url, ...arguments_);
    };

    XMLHttpRequest.prototype.send = function (body) {
        if (!this.__maracujaRequestUrl?.includes('/livewire/upload-file') || !(body instanceof FormData)) return send.call(this, body);

        const entries = Array.from(body.entries());

        Promise.all(entries.map(async ([key, value]) => {
            if (!(value instanceof File)) return [key, value];

            const contents = await value.arrayBuffer();

            return [key, new File([contents], value.name, {
                type: value.type,
                lastModified: value.lastModified,
            })];
        })).then((copiedEntries) => {
            const uploadBody = new FormData();

            copiedEntries.forEach(([key, value]) => uploadBody.append(key, value));

            send.call(this, uploadBody);
        }).catch(() => send.call(this, body));
    };
})();
