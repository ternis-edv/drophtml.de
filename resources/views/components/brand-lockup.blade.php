@props(['inverse' => false])

<span {{ $attributes->class(['inline-flex items-center gap-2 sm:gap-2.5']) }} aria-label="drophtml.de">
    <x-app-logo-icon @class(['size-7 shrink-0 sm:size-8', 'text-[#A5B4FC]' => $inverse, 'text-[#4F46E5] dark:text-[#A5B4FC]' => ! $inverse]) />
    <x-app-wordmark @class(['h-5 w-auto sm:h-7', 'text-[#F5F5FA]' => $inverse, 'text-[#171923] dark:text-[#F5F5FA]' => ! $inverse]) />
</span>
