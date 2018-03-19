@extends('app')

@section('content')

	<div>
	    @if (count($mainCats) > 0)
			@foreach($mainCats as $mainCat)					
				<a href="/get-offers-by-id?categoryId={{$mainCat['id']}}">
					<span class="btn btn-primary btn-sm mb-2">{{ $mainCat['name'] }}</span>
				</a>							
		    @endforeach
		@endif
	</div>	

	<div class="row justify-content-center">
	    @if (count($offers) > 0)
			@foreach($offers as $offer)
					<div class="p-2">						
						<div class="card" style="width: 18rem;">
							<div style="overflow: hidden; height: 220px;">
								<a href="/view/{{ $offer['id'] }}">
									<img class="card-img-top" src="{{ isset($offer['images']) ? $offer['images'] : '/img/image_not_available.png' }}" alt="Card image cap">
								</a>
							</div>
							<div class="card-body">
								<p class="card-text">{{ str_limit($offer['text'], 30) }}</p>
							</div>
						</div>						
					</div>				
		    @endforeach
		@endif	
	</div>

@endsection
