export function initAssistant(root = document) {
    const widget = root.querySelector('[data-assistant-widget]');
    if (!widget) return;

    const panel = widget.querySelector('[data-assistant-panel]');
    const form = widget.querySelector('[data-assistant-form]');
    const contactForm = widget.querySelector('[data-assistant-contact-form]');
    const input = widget.querySelector('[data-assistant-input]');
    const messages = widget.querySelector('[data-assistant-messages]');
    const status = widget.querySelector('[data-assistant-status]');
    const reference = widget.querySelector('[data-assistant-reference]');
    const handover = widget.querySelector('[data-assistant-handover]');
    let acceptingMessages = true;

    const request = async (url, options = {}) => {
        const response = await fetch(url, {
            headers: {Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? ''},
            credentials: 'same-origin',
            ...options,
        });
        if (!response.ok) {
            const error = new Error('Assistant request failed');
            error.status = response.status;
            throw error;
        }
        return response.json();
    };

    const render = (payload) => {
        acceptingMessages = !payload.session || payload.session.accepting_messages;
        messages.replaceChildren(...payload.messages.map((message) => {
            const item = document.createElement('p');
            item.className = `assistant-widget__message assistant-widget__message--${message.author}`;
            item.textContent = message.content;
            return item;
        }));
        reference.textContent = payload.session ? `Référence ${payload.session.reference}` : '';
        form.hidden = !acceptingMessages;
        contactForm.hidden = !(payload.contact_request?.enabled && payload.session?.handover_requested && !payload.session?.contact_requested);
        handover.hidden = !payload.whatsapp_url;
        if (payload.whatsapp_url) handover.href = payload.whatsapp_url;
        messages.scrollTop = messages.scrollHeight;
    };

    const open = async () => {
        panel.hidden = false;
        widget.querySelector('[data-assistant-toggle]').setAttribute('aria-expanded', 'true');
        try {
            render(await request(widget.dataset.historyUrl));
            if (!form.hidden) input.focus();
        } catch {
            status.textContent = 'L’assistant est momentanément indisponible.';
        }
    };

    widget.querySelector('[data-assistant-toggle]').addEventListener('click', open);
    widget.querySelector('[data-assistant-close]').addEventListener('click', () => {
        panel.hidden = true;
        widget.querySelector('[data-assistant-toggle]').setAttribute('aria-expanded', 'false');
    });

    form.addEventListener('submit', async (event) => {
        event.preventDefault();
        const content = input.value.trim();
        if (!content || !acceptingMessages) return;
        input.disabled = true;
        try {
            render(await request(widget.dataset.messageUrl, {method: 'POST', body: JSON.stringify({content, website: form.elements.website.value, entry_url: location.href})}));
            input.value = '';
        } catch (error) {
            status.textContent = error.status === 409 ? 'L’assistant ne peut plus répondre à cette demande.' : 'Le message n’a pas pu être envoyé.';
        } finally {
            input.disabled = false;
            if (!form.hidden) input.focus();
        }
    });

    contactForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        const data = new FormData(contactForm);
        const email = String(data.get('email') ?? '').trim();
        const phone = String(data.get('phone') ?? '').trim();
        if (!email && !phone) {
            status.textContent = 'Indiquez au moins une adresse e-mail ou un numéro de téléphone.';
            return;
        }
        const submit = contactForm.querySelector('button[type="submit"]');
        submit.disabled = true;
        try {
            render(await request(widget.dataset.contactRequestUrl, {method: 'POST', body: JSON.stringify({name: data.get('name'), email: email || null, phone: phone || null, preferred_channel: data.get('preferred_channel'), consent: data.get('consent') === 'on'})}));
            status.textContent = 'Votre demande est enregistrée.';
        } catch {
            status.textContent = 'Votre demande n’a pas pu être enregistrée. Vérifiez les informations indiquées puis réessayez.';
        } finally {
            submit.disabled = false;
        }
    });
}
