<!-- auth-page content -->
<div class="d-flex min-vh-100">
    <div class="col-sm-7 bg-white d-flex flex-column justify-content-center">
        <div class="ps-4 my-auto d-flex align-items-center position-absolute" style="height: 73px; top: 0;">
            <img src="{{ asset('assets/images/logo-kke-sakip.svg') }}" alt="" height="50">
        </div>
        <div class="mx-auto bg-white border border-1 rounded shadow-lg" style="width: 500px">
            <div class="p-5 ">
                <div>
                    <h5 class="text-dark fw-bold">Selamat Datang Kembali !</h5>
                    <p class="text-muted">Masuk untuk melanjutkan ke Dashboard KKE SAKIP</p>
                </div>

                <div class="mt-4">
                    <form action="index.html"
                        <div class="mb-3">
                            <label for="username" class="form-label">Email</label>
                            <input type="text" class="form-control" id="username" placeholder="Masukkan email">
                        </div>

                        <div class="mb-3">
                            <div class="float-end">
                                <a href="auth-pass-reset-cover.html" class="text-muted">Forgot
                                    password?</a>
                            </div>
                            <label class="form-label" for="password-input">Password</label>
                            <div class="position-relative auth-pass-inputgroup mb-3">
                                <input type="password" class="form-control pe-5 password-input"
                                    placeholder="Masukkan password" id="password-input">
                                <button
                                    class="btn btn-link position-absolute end-0 top-0 text-decoration-none shadow-none text-muted password-addon"
                                    type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                            </div>
                        </div>

                        {{-- <div class="form-check">
                            <input class="form-check-input" type="checkbox" value="" id="auth-remember-check">
                            <label class="form-check-label" for="auth-remember-check">Ingat
                                saya</label>
                        </div> --}}

                        <div class="mt-4">
                            <button class="btn btn-success w-100" type="submit">Masuk</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <!-- end col -->

    <div class="col-sm-5 my-auto">
        <div class="p-lg-5 p-4">
            {{-- <img src="{{ asset('assets/images/logo-trenggalek.png') }}" alt=""> --}}
            {{-- <div class="bg-overlay"></div> --}}
            {{-- <dotlottie-wc src="https://lottie.host/df3bd7c4-a39b-4937-9ed7-c0b83f3ff6f7/INn471r6gz.lottie"
                style="width: 300px;height: 300px" autoplay loop></dotlottie-wc> --}}
            <iframe class="col-sm-12" style="width:100%;height:400px"
                src="https://lottie.host/embed/df3bd7c4-a39b-4937-9ed7-c0b83f3ff6f7/INn471r6gz.lottie"></iframe>
            <div class="col-sm-12 my-3 align-self-center text-center">
                <h1 class="text-dark fw-bold align-self-center">Dashboard KKE SAKIP</h1>
                <p class="text-dark pt-2">Kertas Kerja Evaluasi Sistem Akuntabilitas Kinerja Instansi Pemerintah</p>
            </div>
        </div>
    </div>
    <!-- end col -->

</div>
<!-- end row -->
