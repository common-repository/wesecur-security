<div class="wesecursecurity-header">
    <div class="wesecursecurity-right">
        <a href="https://www.wesecur.com" class="wesecur-logo" title="WeSecur"  target="_blank">
            <img src="{$logo}" alt="WeSecur">
        </a>
    </div>
    <nav class="wesecursecurity-left skew-menu">
        <ul>
            {foreach $pages as $page}
                <li class="{if $page->isPageSelected()}selected{/if}"><a href="{$page->getUrl()}">{$page->getName()}</a></li>
            {/foreach}

        </ul>
        {if $hasMalware}
                <ul>
                        <li class="wesecur-menu-red"><a href="{$need_help_url}" target="_blank">{$need_help_btn_text}</a></li>
                </ul>
        {/if}
    </nav>
</div>