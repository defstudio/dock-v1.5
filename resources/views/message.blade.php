<div class="py-1 px-2">
    <span class="px-1 bg-{{$background}} text-{{$color}}">{{$label}}</span>
    @if(isset($message))
        <span class="ml-1">{!! $message !!}</span>
    @endif
</div>
