@props(['url'])
<tr>
    <td class="header">
        <a href="https://app.shipio.app/login" style="display: inline-block;">
            @if (trim($slot) === 'EZSHIP')
                <img src="https://i.ibb.co/djW3vsz/Logo-01-1.png" class="logo" alt="Laravel Logo">
            @else
                {{ $slot }}
            @endif
        </a>
    </td>
</tr>
