<div class="rbt-toolbar rbt-toolbar--bottom d-block d-xl-none">
    <div class="container p--0">
        <div class="row row row--0">
            <div class="col-md-12">
                <ul class="rbt-quick-access onepagenav">
                    <li class="rbt-access-box">
                        <a href="#" class="rbt-round-btn has-rbt-md-fsize">
                            <i class="fa-regular fa-bag-shopping"></i>
                            <span class="rbt-toolbar-label"> Boutique</span>
                        </a>
                    </li>

                    <li class="rbt-access-box rbt-wishlist">
                        <a class="rbt-round-btn has-rbt-md-fsize" href="#!" data-bs-toggle="modal" data-bs-target="#wishlistModal">
                            <i class="fa-regular fa-heart"></i>
                            <div class="access-box-count">0</div>
                            <span class="rbt-toolbar-label"> Favoris</span>
                        </a>
                    </li>

                    <li class="rbt-access-box">
                        <a class="rbt-common-search-trigger-active rbt-round-btn has-rbt-md-fsize rbt-modern-close-btn" href="{{ route('home') }}">
                            <i class="fa-regular fa-house search-icon"></i>
                            <div class="modern-close-wrapper"></div>
                            <span class="rbt-toolbar-label"> Accueil</span>
                        </a>
                    </li>

                    <li class="rbt-access-box">
                        <a href="#" class="rbt-round-btn has-rbt-md-fsize">
                            <i class="fa-regular fa-code-compare"></i>
                            <div class="access-box-count">0</div>
                            <span class="rbt-toolbar-label"> Comparer</span>
                        </a>
                    </li>

                    <li class="rbt-access-box">
                        @auth
                            <a href="#" class="rbt-round-btn has-rbt-md-fsize" data-logout>
                                <i class="fa-regular fa-right-from-bracket"></i>
                                <span class="rbt-toolbar-label"> Déconnexion</span>
                            </a>
                        @else
                            <a href="#!" class="rbt-round-btn has-rbt-md-fsize" data-bs-toggle="modal" data-bs-target="#signinModal">
                                <i class="fa-regular fa-user"></i>
                                <span class="rbt-toolbar-label"> Profil</span>
                            </a>
                        @endauth
                    </li>
                </ul>
            </div>
        </div>
    </div>
</div>
