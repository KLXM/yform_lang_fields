import re

with open('lib/KLXM/YformLangFields/LangHelper.php', 'r') as f:
    text = f.read()

bad1 = """    public static function buildListPopover(array $parsed, string $mode = 'text', int $preferredClangId = 0): string
    {
        if (empty($parsed)) {
            return '<span>-</span>';
        }

        $spans = [];
        $firstNonEmpty = null;

        foreach ($parsed as $item) {"""

new1 = """    public static function buildListPopover(array $parsed, string $mode = 'text', int $preferredClangId = 0): string
    {
        $onlineClangs = rex_clang::getAll(true);
        $totalOnline = count($onlineClangs);

        if (empty($parsed)) {
            $classes = $totalOnline > 0 ? 'ylf-list-entry ylf-is-incomplete' : 'ylf-list-entry';
            return '<span class="' . $classes . '" data-ylf-default="0"><span>-</span></span>';
        }

        $spans = [];
        $firstNonEmpty = null;
        $translatedOnlineCount = 0;

        foreach ($parsed as $item) {"""

bad2 = """            if ('' === $text) {
                continue;
            }

            if (null === $firstNonEmpty) {
                $firstNonEmpty = $clangId;
            }"""

new2 = """            if ('' === $text) {
                continue;
            }

            if (isset($onlineClangs[$clangId])) {
                $translatedOnlineCount++;
            }

            if (null === $firstNonEmpty) {
                $firstNonEmpty = $clangId;
            }"""

bad3 = """        return '<span class="ylf-list-entry" data-ylf-default="' . $default . '">'
            . implode('', $spans)
            . '</span>';"""

new3 = """        $isIncomplete = $translatedOnlineCount < $totalOnline;
        $classes = 'ylf-list-entry';
        if ($isIncomplete) {
            $classes .= ' ylf-is-incomplete';
        }

        return '<span class="' . $classes . '" data-ylf-default="' . $default . '">'
            . implode('', $spans)
            . '</span>';"""


text = text.replace(bad1, new1)
text = text.replace(bad2, new2)
text = text.replace(bad3, new3)

with open('lib/KLXM/YformLangFields/LangHelper.php', 'w') as f:
    f.write(text)

