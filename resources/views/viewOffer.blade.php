@extends('app')

@section('content')


<ul class="list-group">
	<li class="list-group-item">
		@if (isset($image))
			<a href=/media{{ substr($image, 15) }}>
				<img src="{{ $image }}-med" alt="Card image cap">
			</a>	
		@else
			<img src="/img/image_not_available.png" alt="Card image cap">
		@endif
	</li>
		<li class="list-group-item">
		Цена: {{ $offer['price'] }} {{ $offer['currency'] }}
	</li>
	</li>
		<li class="list-group-item">
		Телефон: {{ $offer['phone'] }}
	</li>
	<li class="list-group-item">
		{{ $offer['text'] }}
	</li>
</ul>

@endsection
