@extends('app')

@section('title')
	@if(isset($searchString))
		поиск по запросу "{{$searchString}}""
	@endif
@endsection

@section('content')

	<div>
		@if(count($breadCrumb) > 0)
		<nav aria-label="breadcrumb">
			<ol class="breadcrumb">
				@foreach($breadCrumb as $link)
					<li class="breadcrumb-item"><a href="/search-page?string={{$link['name']}}">{{$link['name']}}</a></li>
				@endforeach
			</ol>
		</nav>
		@endif
		
		@if(count($filters) > 0)
				@foreach($filters as $batton)
					<a href="/get-offers-by-id?categoryId={{$batton['id']}}">
						<span class="btn btn-primary btn-sm mb-2">{{$batton['name']}}</span>
					</a>
				@endforeach
		@endif
	</div>

	<div>
		@if(isset($searchString))
		Результаты поиска по запросу "{{$searchString}}":
		@endif
	</div>

	<div class="row justify-content-center">
	    @if (count($offers) > 0)
			@foreach($offers as $offer)
					<div class="p-2">						
						<div class="card" style="width: 18rem;">
							<div style="overflow: hidden; height: 220px;">
								<a href="/view/{{ $offer['id'] }}">
									<img class="card-img-top" src="{{ isset($offer['images']) ? $offer['images'] : '/img/image_not_available.png' }}" alt="Card image cap" >
								</a>
							</div>
							<div class="card-body">
								<p class="card-text">{{ str_limit($offer['text'], 30) }}</p>
							</div>
						</div>						
					</div>				
		    @endforeach
		@else
			Not found!!!
		@endif	
	</div>

@endsection
