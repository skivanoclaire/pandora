@extends('layouts.app')

@section('title', $title . ' — Literasi Data')

@section('content')
{{-- Scroll Progress Bar --}}
<div id="progress-bar" class="fixed top-16 left-0 lg:left-[260px] right-0 h-0.5 z-40 bg-transparent">
    <div id="progress-fill" class="h-full bg-gradient-to-r from-pandora-accent to-pandora-success w-0 transition-none"></div>
</div>

{{-- Sticky Breadcrumb --}}
<div class="sticky top-16 z-30 -mx-4 md:-mx-6 lg:-mx-8 px-4 md:px-6 lg:px-8 py-2.5 bg-pandora-dark/90 backdrop-blur-md border-b border-white/5 mb-6">
    <div class="flex items-center gap-2 text-xs text-pandora-muted">
        <a href="{{ route('literasi-data.index') }}" class="hover:text-pandora-accent transition-colors flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            Literasi Data
        </a>
        <svg class="w-3 h-3 text-pandora-muted/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <a href="{{ route('literasi-data.category', $category) }}" class="hover:text-pandora-accent transition-colors">{{ $meta['title'] }}</a>
        <svg class="w-3 h-3 text-pandora-muted/50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        <span class="text-pandora-text truncate max-w-[250px]">{{ $title }}</span>
    </div>
</div>

{{-- Header --}}
<div class="mb-8">
    <div class="flex flex-wrap items-center gap-2 mb-3">
        @if($level)
            @php
                $levelColors = [
                    'Pemula' => 'bg-pandora-accent/15 text-pandora-accent border-pandora-accent/30',
                    'Menengah' => 'bg-pandora-gold/15 text-pandora-gold border-pandora-gold/30',
                    'Lanjut' => 'bg-pandora-danger/15 text-pandora-danger border-pandora-danger/30',
                ];
                $levelClass = $levelColors[$level] ?? 'bg-pandora-accent/15 text-pandora-accent border-pandora-accent/30';
            @endphp
            <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold border {{ $levelClass }}">
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                {{ $level }}
            </span>
        @endif
        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-full text-[11px] font-semibold bg-{{ $meta['color'] }}/15 text-{{ $meta['color'] }} border border-{{ $meta['color'] }}/30">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/></svg>
            {{ $meta['title'] }}
        </span>
    </div>
    <h1 class="text-2xl md:text-3xl font-bold text-white leading-tight">{{ $title }}</h1>
</div>

{{-- Content Card --}}
<div class="bg-pandora-surface rounded-2xl border border-white/5 p-5 md:p-8 lg:p-10 shadow-xl shadow-black/20">
    <article id="article-content" class="literasi-prose max-w-none">
        {!! $html !!}
    </article>
</div>

{{-- Prev/Next Navigation Cards --}}
<div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mt-8">
    @if($nav['prev'])
        <a href="{{ route('literasi-data.show', [$category, $nav['prev']['slug']]) }}"
           class="group flex items-center gap-4 bg-pandora-surface rounded-xl border border-white/5 p-4 hover:border-pandora-accent/30 hover:bg-pandora-surface-light transition-all duration-300">
            <div class="w-10 h-10 rounded-lg bg-pandora-accent/10 flex items-center justify-center flex-shrink-0 group-hover:bg-pandora-accent/20 transition-colors">
                <svg class="w-5 h-5 text-pandora-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </div>
            <div class="min-w-0">
                <span class="text-[10px] uppercase tracking-wider text-pandora-muted font-medium">Sebelumnya</span>
                <p class="text-sm font-medium text-pandora-text group-hover:text-pandora-accent transition-colors truncate">{{ $nav['prev']['title'] }}</p>
            </div>
        </a>
    @else
        <div></div>
    @endif

    @if($nav['next'])
        <a href="{{ route('literasi-data.show', [$category, $nav['next']['slug']]) }}"
           class="group flex items-center justify-end gap-4 bg-pandora-surface rounded-xl border border-white/5 p-4 hover:border-pandora-success/30 hover:bg-pandora-surface-light transition-all duration-300 text-right">
            <div class="min-w-0">
                <span class="text-[10px] uppercase tracking-wider text-pandora-muted font-medium">Selanjutnya</span>
                <p class="text-sm font-medium text-pandora-text group-hover:text-pandora-success transition-colors truncate">{{ $nav['next']['title'] }}</p>
            </div>
            <div class="w-10 h-10 rounded-lg bg-pandora-success/10 flex items-center justify-center flex-shrink-0 group-hover:bg-pandora-success/20 transition-colors">
                <svg class="w-5 h-5 text-pandora-success" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>
    @endif
</div>

{{-- Back to Top Button --}}
<button id="back-to-top"
        onclick="window.scrollTo({top: 0, behavior: 'smooth'})"
        class="fixed bottom-6 right-6 z-50 w-10 h-10 rounded-full bg-pandora-accent/90 text-white shadow-lg shadow-pandora-accent/30 flex items-center justify-center opacity-0 translate-y-4 pointer-events-none transition-all duration-300 hover:bg-pandora-accent hover:scale-110">
    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
</button>

{{-- Inline Styles for Prose --}}
<style>
    /* ---- Literasi Prose Theme ---- */
    .literasi-prose {
        color: #8899aa;
        font-size: 0.925rem;
        line-height: 1.8;
    }

    /* Remove the first h1 since we already show it above */
    .literasi-prose > h1:first-child {
        display: none;
    }

    /* Also hide metadata line (bold Level/Kategori line right after h1) */
    .literasi-prose > h1:first-child + p {
        display: none;
    }

    /* Headings */
    .literasi-prose h2 {
        color: #e0e6ed;
        font-size: 1.25rem;
        font-weight: 700;
        margin-top: 2.5rem;
        margin-bottom: 1rem;
        padding-bottom: 0.5rem;
        padding-left: 0.875rem;
        border-left: 3px solid #00b4d8;
        border-bottom: 1px solid rgba(255,255,255,0.06);
        position: relative;
    }
    .literasi-prose h3 {
        color: #e0e6ed;
        font-size: 1.05rem;
        font-weight: 600;
        margin-top: 2rem;
        margin-bottom: 0.75rem;
    }
    .literasi-prose h4 {
        color: #e0e6ed;
        font-size: 0.95rem;
        font-weight: 600;
        margin-top: 1.5rem;
        margin-bottom: 0.5rem;
    }

    /* Paragraphs */
    .literasi-prose p {
        margin-bottom: 1rem;
    }

    /* Links */
    .literasi-prose a {
        color: #00b4d8;
        text-decoration: none;
        border-bottom: 1px solid transparent;
        transition: border-color 0.2s;
    }
    .literasi-prose a:hover {
        border-bottom-color: #00b4d8;
    }

    /* Strong */
    .literasi-prose strong {
        color: #e0e6ed;
        font-weight: 600;
    }

    /* Inline code */
    .literasi-prose code:not(pre code) {
        color: #00b4d8;
        background: #0a1628;
        padding: 0.15em 0.4em;
        border-radius: 0.25rem;
        font-size: 0.85em;
        border: 1px solid rgba(0,180,216,0.15);
    }

    /* Code blocks (pre) */
    .literasi-prose pre {
        background: #0a1628;
        border: 1px solid rgba(255,255,255,0.08);
        border-radius: 0.75rem;
        padding: 0;
        margin: 1.5rem 0;
        overflow: hidden;
        position: relative;
    }
    .literasi-prose pre code {
        display: block;
        padding: 1rem 1.25rem;
        overflow-x: auto;
        font-size: 0.82rem;
        line-height: 1.7;
        color: #e0e6ed;
    }

    /* Code block header (injected by JS) */
    .code-block-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.4rem 1rem;
        background: rgba(255,255,255,0.03);
        border-bottom: 1px solid rgba(255,255,255,0.06);
        font-size: 0.7rem;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #8899aa;
    }
    .copy-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.5rem;
        border-radius: 0.25rem;
        background: transparent;
        border: 1px solid rgba(255,255,255,0.1);
        color: #8899aa;
        font-size: 0.65rem;
        cursor: pointer;
        transition: all 0.2s;
        text-transform: uppercase;
        letter-spacing: 0.03em;
    }
    .copy-btn:hover {
        background: rgba(0,180,216,0.1);
        color: #00b4d8;
        border-color: rgba(0,180,216,0.3);
    }
    .copy-btn.copied {
        color: #00c48c;
        border-color: rgba(0,196,140,0.3);
    }

    /* Tables */
    .literasi-prose table {
        width: 100%;
        border-collapse: collapse;
        margin: 1.5rem 0;
        font-size: 0.85rem;
        border-radius: 0.5rem;
        overflow: hidden;
    }
    .literasi-prose thead th {
        background: rgba(10,22,40,0.6);
        color: #8899aa;
        font-size: 0.75rem;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        padding: 0.65rem 0.875rem;
        text-align: left;
        border-bottom: 1px solid rgba(255,255,255,0.08);
    }
    .literasi-prose tbody td {
        padding: 0.6rem 0.875rem;
        border-bottom: 1px solid rgba(255,255,255,0.04);
        color: #8899aa;
    }
    .literasi-prose tbody tr:hover {
        background: rgba(0,180,216,0.04);
    }
    .literasi-prose tbody tr:last-child td {
        border-bottom: none;
    }

    /* Blockquotes */
    .literasi-prose blockquote {
        border-left: 3px solid #f0a500;
        background: rgba(240,165,0,0.05);
        margin: 1.5rem 0;
        padding: 1rem 1.25rem;
        border-radius: 0 0.5rem 0.5rem 0;
        color: #8899aa;
    }
    .literasi-prose blockquote p:last-child {
        margin-bottom: 0;
    }

    /* Lists */
    .literasi-prose ul, .literasi-prose ol {
        margin: 1rem 0;
        padding-left: 1.5rem;
    }
    .literasi-prose li {
        margin-bottom: 0.4rem;
        color: #8899aa;
    }
    .literasi-prose li::marker {
        color: #00b4d8;
    }
    .literasi-prose ol li::marker {
        color: #f0a500;
        font-weight: 600;
    }

    /* Horizontal rules */
    .literasi-prose hr {
        border: none;
        border-top: 1px solid rgba(255,255,255,0.06);
        margin: 2rem 0;
    }

    /* Images */
    .literasi-prose img {
        border-radius: 0.75rem;
        border: 1px solid rgba(255,255,255,0.08);
        margin: 1.5rem 0;
        max-width: 100%;
    }

    /* Scroll reveal animation */
    .reveal-section {
        opacity: 0;
        transform: translateY(20px);
        transition: opacity 0.6s ease-out, transform 0.6s ease-out;
    }
    .reveal-section.revealed {
        opacity: 1;
        transform: translateY(0);
    }

    /* KaTeX overrides for dark theme */
    .katex { color: #e0e6ed; }
    .katex-display {
        margin: 1.5rem 0;
        padding: 1rem;
        background: rgba(10,22,40,0.5);
        border-radius: 0.5rem;
        border: 1px solid rgba(255,255,255,0.05);
        overflow-x: auto;
    }

    /* Prism overrides */
    .literasi-prose pre code .token.comment,
    .literasi-prose pre code .token.prolog,
    .literasi-prose pre code .token.doctype,
    .literasi-prose pre code .token.cdata { color: #637777; }
    .literasi-prose pre code .token.punctuation { color: #8899aa; }
    .literasi-prose pre code .token.property,
    .literasi-prose pre code .token.tag,
    .literasi-prose pre code .token.boolean,
    .literasi-prose pre code .token.number,
    .literasi-prose pre code .token.constant,
    .literasi-prose pre code .token.symbol { color: #f78c6c; }
    .literasi-prose pre code .token.selector,
    .literasi-prose pre code .token.attr-name,
    .literasi-prose pre code .token.string,
    .literasi-prose pre code .token.char,
    .literasi-prose pre code .token.builtin { color: #00c48c; }
    .literasi-prose pre code .token.operator,
    .literasi-prose pre code .token.entity,
    .literasi-prose pre code .token.url { color: #89ddff; }
    .literasi-prose pre code .token.atrule,
    .literasi-prose pre code .token.attr-value,
    .literasi-prose pre code .token.keyword { color: #00b4d8; }
    .literasi-prose pre code .token.function,
    .literasi-prose pre code .token.class-name { color: #f0a500; }
    .literasi-prose pre code .token.regex,
    .literasi-prose pre code .token.important,
    .literasi-prose pre code .token.variable { color: #f78c6c; }
</style>
@endsection

@push('scripts')
{{-- KaTeX --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.css">
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/katex.min.js"></script>
<script defer src="https://cdn.jsdelivr.net/npm/katex@0.16.11/dist/contrib/auto-render.min.js"></script>

{{-- Prism.js --}}
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/themes/prism-tomorrow.min.css">
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/prism.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-python.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-sql.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-bash.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/prismjs@1.29.0/components/prism-json.min.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const article = document.getElementById('article-content');
    if (!article) return;

    // 1. KaTeX auto-render
    if (typeof renderMathInElement !== 'undefined') {
        renderMathInElement(article, {
            delimiters: [
                {left: '$$', right: '$$', display: true},
                {left: '$', right: '$', display: false},
                {left: '\\(', right: '\\)', display: false},
                {left: '\\[', right: '\\]', display: true}
            ],
            throwOnError: false
        });
    } else {
        // auto-render loads deferred, wait for it
        document.querySelector('script[src*="auto-render"]').addEventListener('load', function() {
            renderMathInElement(article, {
                delimiters: [
                    {left: '$$', right: '$$', display: true},
                    {left: '$', right: '$', display: false},
                    {left: '\\(', right: '\\)', display: false},
                    {left: '\\[', right: '\\]', display: true}
                ],
                throwOnError: false
            });
        });
    }

    // 2. Enhance code blocks with language header and copy button
    article.querySelectorAll('pre').forEach(function(pre) {
        const code = pre.querySelector('code');
        if (!code) return;

        // Detect language from class
        let lang = 'code';
        const cls = code.className || '';
        const match = cls.match(/language-(\w+)/);
        if (match) {
            lang = match[1];
        }

        // Create header
        const header = document.createElement('div');
        header.className = 'code-block-header';
        header.innerHTML = '<span>' + lang + '</span><button class="copy-btn" onclick="copyCode(this)"><svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> Salin</button>';
        pre.insertBefore(header, code);
    });

    // 3. Apply Prism highlighting
    if (typeof Prism !== 'undefined') {
        Prism.highlightAllUnder(article);
    }

    // 4. Scroll reveal animation on h2 sections
    const headings = article.querySelectorAll('h2');
    headings.forEach(function(h2) {
        // Wrap h2 and its siblings until next h2 in a reveal section
        const section = document.createElement('div');
        section.className = 'reveal-section';
        h2.parentNode.insertBefore(section, h2);
        section.appendChild(h2);

        let next = section.nextSibling;
        while (next) {
            const nextEl = next;
            next = next.nextSibling;
            if (nextEl.nodeType === 1 && nextEl.tagName === 'H2') break;
            section.appendChild(nextEl);
        }
    });

    // Intersection Observer for reveal
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1, rootMargin: '0px 0px -40px 0px' });

    document.querySelectorAll('.reveal-section').forEach(function(el) {
        observer.observe(el);
    });

    // 5. Scroll progress bar
    const progressFill = document.getElementById('progress-fill');
    window.addEventListener('scroll', function() {
        const scrollTop = window.scrollY;
        const docHeight = document.documentElement.scrollHeight - window.innerHeight;
        const pct = docHeight > 0 ? (scrollTop / docHeight) * 100 : 0;
        progressFill.style.width = pct + '%';

        // Back to top button
        const btn = document.getElementById('back-to-top');
        if (scrollTop > 400) {
            btn.classList.remove('opacity-0', 'translate-y-4', 'pointer-events-none');
            btn.classList.add('opacity-100', 'translate-y-0', 'pointer-events-auto');
        } else {
            btn.classList.add('opacity-0', 'translate-y-4', 'pointer-events-none');
            btn.classList.remove('opacity-100', 'translate-y-0', 'pointer-events-auto');
        }
    });

    // 6. Wrap tables in scrollable container
    article.querySelectorAll('table').forEach(function(table) {
        const wrapper = document.createElement('div');
        wrapper.style.overflowX = 'auto';
        wrapper.style.borderRadius = '0.5rem';
        wrapper.style.border = '1px solid rgba(255,255,255,0.08)';
        table.parentNode.insertBefore(wrapper, table);
        wrapper.appendChild(table);
    });
});

// Copy code function
function copyCode(btn) {
    const pre = btn.closest('pre');
    const code = pre.querySelector('code');
    const text = code.textContent;
    navigator.clipboard.writeText(text).then(function() {
        btn.classList.add('copied');
        btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg> Tersalin!';
        setTimeout(function() {
            btn.classList.remove('copied');
            btn.innerHTML = '<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="9" y="9" width="13" height="13" rx="2"/><path d="M5 15H4a2 2 0 01-2-2V4a2 2 0 012-2h9a2 2 0 012 2v1"/></svg> Salin';
        }, 2000);
    });
}
</script>
@endpush
