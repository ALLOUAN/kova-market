<div class="rbt-search-dropdown rbt-common-search-dropdown-activation">
    <div class="wrapper">
        <div class="row">
            <div class="col-lg-12">
                <div class="rbt-component-section-title border-0 p-0 text-center">
                    <h2 class="rbt-title text-start text-md-center"><span class="rbt-bold--text">Search For
                            Products</span></h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-12">
                <form class="rbt-search-form">
                    <div class="input-sectition position-relative w-100 mr--12 mr_sm--4">
                        <input class="search-input" type="text" placeholder="What Are You Looking For?">
                        <i class="fa-sharp fa-regular inner-search-icon fa-magnifying-glass"></i>
                        <button class="media-search-btn media-search-popupactivation">
                            <i class="fa-sharp fa-regular fa-camera"></i>
                        </button>
                    </div>
                    <div class="submit-btn">
                        <a class="rbt-btn btn-md" href="#">Search</a>
                    </div>
                    <div class="rbt-media-search-section">
                        <div class="rbt-media-wrapper">
                            <div class="section-title"><span class="title b1">Find product inspiration with Image
                                    Search</span></div>
                            <div class="rbt-file-upload-container">
                                <input type="file" class="fileInput" multiple="" hidden="">
                                <div class="file-upload-area fileUploadArea">
                                    <div class="file-upload-content">
                                        <span class="rbt-icon"><i class="fa-solid fa-cloud-arrow-up"></i></span>
                                        <p class="rbt-title">Drag & Drop Files Here <span class="rbt-text-color-gray-400">Or</span></p>
                                        <button class="browseFilesButton rbt-btn rbt-btn-sm">Browse Files</button>
                                    </div>
                                    <div class="fileList file-list"></div>
                                </div>
                                <p class="fileCount">0 of 10</p>
                            </div>
                            <div class="rbt-copy-link-part rbt-text-copy-activation">
                                <input class="rbt-copy-value-field" type="text" value="{{ url('/') }}/wishlist" readonly="">
                                <button class="rbt-btn rbt-btn-xs has-left-icon rbt-copy-btn" data-tooltip="Copy">
                                    <i class="fa-regular fa-copy"></i>
                                    <span class="rbt-btn-text">Copy</span>
                                </button>
                            </div>
                            <button type="button" class="rbt-round-btn rbt-ms-dismiss-btn">
                                <i class="fa-solid fa-xmark"></i>
                            </button>
                        </div>
                    </div>
                    <a href="javascript:void(0);" class="rbt-ms-dismiss-outsider"></a>
                </form>
            </div>
        </div>
        <div class="rbt-search-scroll-vertical-wrapper rbt-scroll-vertical">
            <div class="inner">
                <div class="row row--0">
                    <div class="col-lg-12">
                        <div class="border-0 p-0 text-left title-sm-fsize">
                            <h2 class="title"><span class="rbt-bold--text">Popular searches</span></h2>
                        </div>
                    </div>

                    <div class="rbt-search-list-wrapper rbt-tag-list rbt-tag-list-rounded-lg">
                        @foreach (config('storefront.search.popular') as $term)
                            <a href="#">{{ $term }}</a>
                        @endforeach
                    </div>
                </div>

                <div class="rbt-separator-mid ptb--24">
                    <hr class="rbt-separator m-0">
                </div>

                <div class="row row--0">
                    <div class="col-lg-12">
                        <div class="border-0 p-0 text-left title-sm-fsize">
                            <h2 class="title"><span class="rbt-bold--text">Trending Products</span></h2>
                        </div>
                    </div>
                </div>

                <div class="row row--12 m--0 mt_dec--24">
                    @foreach ($trendingProducts as $product)
                        <div class="col-xl-3 col-lg-6 col-md-6 col-sm-6 col-6 mt--24 mt_sm--16">
                            <x-product.card :product="$product" :order="$loop->iteration" heading="h2" />
                        </div>
                    @endforeach
                </div>

            </div>
        </div>

    </div>
</div>
