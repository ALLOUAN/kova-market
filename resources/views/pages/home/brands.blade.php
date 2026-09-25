<div class="rbt-component-area rbt-catagories-area rbt-section-gap2 rbt-bg-color-gray-light">
    <div class="container">
        <div class="rbt-brand-style-one rbt-fshape-box-outline-style rbt-fshape-box-outline-style-extend-width">
            <div class="row">
                <div class="col-lg-12">
                    <x-section-title class="text-left">Favorite
                        Brands</x-section-title>
                </div>
            </div>

            <div class="rbt-fshape-box rbt-fshape-box-py-inc">
                <div class="row row--12 mt_dec--24">
                    @foreach ($brands as $brand)
                        <div class="col-lg-1-5 col-lg-4 col-md-4 col-sm-6 col-6 mt--24">
                            <x-brand.card :brand="$brand" :order="$loop->iteration" />
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
