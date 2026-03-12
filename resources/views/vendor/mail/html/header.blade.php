@props(['url'])

<tr>
    <td class="header">
        <a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
            @if (setting('site_logo'))
                <img src="{{ setting('site_logo') }}" class="logo" alt="{{ setting('site_name', config('app.name')) }}"
                    style="max-height: 72px; width: auto;">
            @else
                {{ setting('site_name', config('app.name')) }}
            @endif
        </a>
    </td>
</tr>
