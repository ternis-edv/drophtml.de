import { basicSetup } from 'codemirror';
import { EditorState, Compartment } from '@codemirror/state';
import { EditorView, keymap } from '@codemirror/view';
import { indentWithTab } from '@codemirror/commands';
import { indentUnit, syntaxHighlighting, HighlightStyle } from '@codemirror/language';
import { openSearchPanel } from '@codemirror/search';
import { tags } from '@lezer/highlight';
// Load language parsers only when their document type is opened.
const languageFor = async (path) => {
    const ext = path?.split('.').pop()?.toLowerCase();
    if (['html', 'htm', 'svg', 'xml'].includes(ext)) return (await import('@codemirror/lang-html')).html();
    if (ext === 'css') return (await import('@codemirror/lang-css')).css();
    if (['js', 'mjs', 'jsx', 'ts', 'tsx'].includes(ext)) {
        return (await import('@codemirror/lang-javascript')).javascript({ jsx: ext.endsWith('x'), typescript: ext.startsWith('ts') });
    }
    if (ext === 'json') return (await import('@codemirror/lang-json')).json();
    if (ext === 'md') return (await import('@codemirror/lang-markdown')).markdown();
    return [];
};

const studioTheme = EditorView.theme({
    '&': { height: '100%', backgroundColor: 'var(--studio-code)', color: '#cbd5e1', fontSize: '13px' },
    '.cm-scroller': { fontFamily: 'var(--font-mono)', lineHeight: '1.85', overflow: 'auto' },
    '.cm-content': { padding: '20px 0', caretColor: '#c4b5fd' },
    '.cm-line': { padding: '0 22px 0 12px' },
    '.cm-gutters': { backgroundColor: 'var(--studio-code)', color: '#526078', border: 'none', minWidth: '48px' },
    '.cm-activeLineGutter': { backgroundColor: '#ffffff06', color: '#c4b5fd' },
    '.cm-activeLine': { backgroundColor: '#ffffff04' },
    '&.cm-focused .cm-selectionBackground, .cm-selectionBackground, ::selection': { backgroundColor: '#6658c550 !important' },
    '.cm-cursor': { borderLeftColor: '#c4b5fd' },
    '.cm-panels': { backgroundColor: '#1c2433', color: '#d6deed' },
    '.cm-search': { padding: '10px', fontFamily: 'var(--font-sans)' },
    '.cm-textfield': { backgroundColor: '#101722', border: '1px solid #394357', borderRadius: '4px', color: '#e2e8f0' },
    '.cm-button': { background: '#30394c', color: '#e2e8f0', border: '1px solid #465169', borderRadius: '4px' },
    '.cm-foldPlaceholder': { backgroundColor: '#30394c', color: '#a5b4fc', border: 'none' },
    '.cm-tooltip': { backgroundColor: '#1c2433', border: '1px solid #394357', color: '#e2e8f0' },
}, { dark: true });

const studioHighlight = HighlightStyle.define([
    { tag: tags.comment, color: '#65758d', fontStyle: 'italic' },
    { tag: [tags.keyword, tags.tagName], color: '#c4a7f5' },
    { tag: [tags.string, tags.attributeValue], color: '#9ed9bb' },
    { tag: [tags.attributeName, tags.propertyName], color: '#91c5ed' },
    { tag: [tags.number, tags.bool, tags.null], color: '#edc08b' },
    { tag: [tags.function(tags.variableName)], color: '#e5c48e' },
    { tag: [tags.angleBracket, tags.punctuation], color: '#8091aa' },
    { tag: tags.heading, color: '#c4a7f5', fontWeight: 'bold' },
]);

// Keep CodeMirror outside Alpine's reactive proxies; Livewire owns the document,
// while the ignored editor DOM owns selection, undo history, and composition.
function registerStudio() {
    window.Alpine.data('codeStudio', (wire) => {
        let editor;
        let currentPath;
        let savedContent = '';
        let saveSnapshot = '';
        let syncing = false;
        const language = new Compartment();
        return {
            dirty: false,
            renamePath: null,
            saving: false,
            explorerOpen: window.innerWidth > 760,
            mode: 'split',
            viewport: 'desktop',
            previewTimestamp: Date.now(),
            line: 1,
            column: 1,
            lines: 1,
            init() {
                this.$nextTick(() => {
                    editor = new EditorView({
                        parent: this.$refs.editor,
                        state: this.stateFor(wire.fileContent),
                    });
                    currentPath = wire.currentFile;
                    savedContent = wire.fileContent;
                    this.highlight();
                    this.stats();
                });
                this.$watch('$wire.currentFile', () => this.$nextTick(() => this.sync()));
                this.$watch('$wire.fileContent', () => this.$nextTick(() => this.sync()));
                this.$watch('mode', () => this.$nextTick(() => editor?.requestMeasure()));
                this.$watch('explorerOpen', () => this.$nextTick(() => editor?.requestMeasure()));
            },
            stateFor(content) {
                return EditorState.create({
                    doc: content || '',
                    extensions: [basicSetup, studioTheme, syntaxHighlighting(studioHighlight), language.of([]), indentUnit.of('  '), keymap.of([indentWithTab]), EditorView.contentAttributes.of({ 'aria-label': 'Code editor', spellcheck: 'false' }), EditorView.updateListener.of((update) => {
                        if (update.docChanged && !syncing) {
                            wire.$set('fileContent', update.state.doc.toString(), false);
                            this.dirty = update.state.doc.toString() !== savedContent;
                        }
                        if (update.docChanged || update.selectionSet) this.stats();
                    })],
                });
            },
            sync() {
                if (!editor || this.saving) return;
                syncing = true;
                if (currentPath !== wire.currentFile) {
                    const previousPath = currentPath;
                    currentPath = wire.currentFile;
                    if (this.renamePath !== previousPath || editor.state.doc.toString() !== wire.fileContent) savedContent = wire.fileContent || '';
                    this.renamePath = null;
                    editor.setState(this.stateFor(wire.fileContent));
                    this.highlight();
                } else if (editor.state.doc.toString() !== wire.fileContent) {
                    editor.dispatch({ changes: { from: 0, to: editor.state.doc.length, insert: wire.fileContent || '' } });
                }
                syncing = false;
                this.dirty = editor.state.doc.toString() !== savedContent;
                this.stats();
            },
            async highlight() {
                const path = wire.currentFile;
                const extension = await languageFor(path);
                if (editor && currentPath === path) editor.dispatch({ effects: language.reconfigure(extension) });
            },
            stats() {
                if (!editor) return;
                const position = editor.state.selection.main.head;
                const row = editor.state.doc.lineAt(position);
                this.line = row.number;
                this.column = position - row.from + 1;
                this.lines = editor.state.doc.lines;
            },
            canLeave() {
                return !this.saving && (!this.dirty || window.confirm('You have unsaved changes. Discard them and continue?'));
            },
            async selectFile(path) {
                if (path === wire.currentFile || !this.canLeave()) return;
                this.renamePath = null;
                await wire.selectFile(path);
                if (window.innerWidth <= 760) this.explorerOpen = false;
            },
            async save() {
                if (this.saving || !wire.currentFile || wire.isBinary || !editor) return;
                saveSnapshot = editor.state.doc.toString();
                wire.$set('fileContent', saveSnapshot, false);
                this.saving = true;
                try {
                    await wire.saveFile();
                } finally {
                    this.saving = false;
                    // Preserve edits made while the save request was in flight.
                    wire.$set('fileContent', editor.state.doc.toString(), false);
                    this.dirty = editor.state.doc.toString() !== savedContent;
                }
            },
            saved() {
                savedContent = saveSnapshot;
                this.dirty = editor?.state.doc.toString() !== savedContent;
                this.reloadPreview();
            },
            reloadPreview() { this.previewTimestamp = Date.now(); },
            find() {
                if (!editor || !wire.currentFile || wire.isBinary) return;
                this.mode = 'editor';
                this.$nextTick(() => { openSearchPanel(editor); editor.requestMeasure(); });
            },
            shortcut(event) {
                if ((event.metaKey || event.ctrlKey) && event.key.toLowerCase() === 's') {
                    event.preventDefault();
                    this.save();
                }
            },
            destroy() { editor?.destroy(); editor = null; },
        };
    });
}

if (window.Alpine) registerStudio();
else document.addEventListener('alpine:init', registerStudio, { once: true });
