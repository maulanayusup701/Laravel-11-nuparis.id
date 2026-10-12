@props(['id' => '', 'en' => '', 'text' => null])
@php
    $idText = $text['id'] ?? $id;
    $enText = $text['en'] ?? $en;
@endphp
<span data-lang="id">{{ $idText }}</span><span data-lang="en" class="hidden">{{ $enText }}</span>
