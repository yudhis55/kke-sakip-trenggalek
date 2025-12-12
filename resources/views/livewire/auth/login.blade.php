<!-- auth-page content -->
<div class="d-flex min-vh-100">
    <div class="col-sm-7 bg-white d-flex flex-column justify-content-center">
        <div class="ps-4 my-auto d-flex align-items-center position-absolute" style="height: 73px; top: 0;">
            <img src="{{ asset('assets/images/logo-kke-sakip.svg') }}" alt="" height="50">
        </div>
        <div class="mx-auto bg-white border border-1 rounded shadow-lg" style="width: 500px">
            <div class="p-5 ">
                <div>
                    <h5 class="text-primary fw-bold">Selamat Datang Kembali !</h5>
                    <p class="text-muted">Masuk untuk melanjutkan ke Dashboard KKE SAKIP</p>
                </div>
                <div class="mt-4">
                    <form wire:submit="login">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input wire:model="email" type="text"
                                class="form-control @error('email') is-invalid @enderror" id="email"
                                placeholder="Masukkan email" autofocus>
                            @error('email')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label class="form-label" for="password-input">Password</label>
                            <div class="position-relative auth-pass-inputgroup mb-3">
                                <input wire:model="password" type="password"
                                    class="form-control pe-5 password-input @error('password') is-invalid @enderror"
                                    placeholder="Masukkan password" name="password" id="password-input" required>
                                <button
                                    class="btn btn-link position-absolute end-0 top-0 text-decoration-none shadow-none text-muted password-addon"
                                    type="button" id="password-addon"><i class="ri-eye-fill align-middle"></i></button>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                        <div class="mt-4">
                            <button class="btn btn-primary w-100" type="submit">Masuk</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="col-sm-5 bg-soft-light d-flex flex-column justify-content-center">
        <div class="p-lg-5 p-4">
            {{-- <iframe class="col-sm-12" style="width:100%;height:400px"
                src="https://lottie.host/embed/df3bd7c4-a39b-4937-9ed7-c0b83f3ff6f7/INn471r6gz.lottie"></iframe> --}}
            <dotlottie-wc src="https://lottie.host/df3bd7c4-a39b-4937-9ed7-c0b83f3ff6f7/INn471r6gz.lottie"
                style="width:100%;height:400px" autoplay loop></dotlottie-wc>
            <div class="col-sm-12 my-3 align-self-center text-center">
                <h1 class="text-dark fw-bold align-self-center">Dashboard KKE SAKIP</h1>
                <p class="text-dark pt-2">Kertas Kerja Evaluasi Sistem Akuntabilitas Kinerja Instansi Pemerintah</p>
            </div>
        </div>
    </div>
    <!-- end col -->
</div>
<!-- end col -->
