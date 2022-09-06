<ul class="mx-2">
    <li>
        <span class="text-green">{!! $question !!}</span>

        @if(empty($choices) && !empty($default))
            &nbsp;<span class='text-gray'> ({{$default}})</span>
        @elseif(!empty($choices))
            @php
                $choices = collect($choices)
                    ->map(function (string|int $choice) use ($default) {
                        return $choice === $default
                        ? "<span class='text-white'>$choice</span>"
                        : $choice;
                    })
                    ->join(", ")
            @endphp

            <span class='text-gray'>&nbsp;[choose: {!! $choices !!}]</span>
        @endif

        @if(!is_bool($allowEmpty) || !empty($default) && $allowEmpty)
            @if($allowEmpty === '')
                <span class='text-gray'>&nbsp;(leave blank to skip)</span>
            @elseif(!is_bool($allowEmpty))
                <span class='text-gray'>&nbsp;(press '{{$allowEmpty}}' to skip)</span>
            @else
                <span class='text-gray'>&nbsp;(press 'x' to skip)</span>
            @endif
        @endif
        :
    </li>
</ul>
