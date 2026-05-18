# Restaurant: {{ $slug }}.menudirect.ca
# AI bots explicitly allowed — restaurants want to be discoverable in AI search.

User-agent: *
Allow: /

User-agent: GPTBot
Allow: /

User-agent: ChatGPT-User
Allow: /

User-agent: ClaudeBot
Allow: /

User-agent: anthropic-ai
Allow: /

User-agent: Google-Extended
Allow: /

User-agent: PerplexityBot
Allow: /

User-agent: CCBot
Allow: /

User-agent: Applebot-Extended
Allow: /

Sitemap: https://{{ $slug }}.menudirect.ca/sitemap.xml
