@extends('layouts.customer')

@section('content')
    <style>
        .summaryWrap{
            min-height: 80vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }
        .summaryCard{
            max-width: 560px;
            width: 100%;
            text-align: center;
            padding: 48px 36px;
            border-radius: 24px;
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.06);
            position: relative;
            overflow: hidden;
        }

        /* Success glow */
        .summaryCard.success::before{
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(68,224,172,0.08) 0%, transparent 60%);
            animation: glowPulse 3s ease-in-out infinite;
        }
        @keyframes glowPulse{
            0%, 100% { opacity: 0.5; transform: scale(1); }
            50% { opacity: 1; transform: scale(1.05); }
        }

        /* Fail subtle */
        .summaryCard.fail::before{
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(239,68,68,0.06) 0%, transparent 60%);
        }

        .summaryIcon{
            width: 100px;
            height: 100px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
            font-size: 44px;
            position: relative;
            z-index: 1;
        }
        .success .summaryIcon{
            background: rgba(68,224,172,0.12);
            color: #44E0AC;
            animation: bounceIn 0.6s ease-out;
        }
        .fail .summaryIcon{
            background: rgba(239,68,68,0.12);
            color: #ef4444;
        }
        @keyframes bounceIn{
            0% { transform: scale(0); opacity: 0; }
            60% { transform: scale(1.2); }
            100% { transform: scale(1); opacity: 1; }
        }

        .summaryTitle{
            font-size: 1.75rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 12px;
            position: relative;
            z-index: 1;
        }
        .summaryDesc{
            color: rgba(255,255,255,0.6);
            font-size: 1.05rem;
            line-height: 1.6;
            margin-bottom: 32px;
            position: relative;
            z-index: 1;
        }

        .amountBig{
            font-size: 2.5rem;
            font-weight: 900;
            margin-bottom: 8px;
            position: relative;
            z-index: 1;
        }
        .success .amountBig{ color: #44E0AC; }
        .fail .amountBig{ color: #ef4444; }

        .amountLabel{
            color: rgba(255,255,255,0.45);
            font-size: 0.9rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 36px;
            position: relative;
            z-index: 1;
        }

        /* Stats row */
        .statsRow{
            display: flex;
            justify-content: center;
            gap: 32px;
            margin-bottom: 36px;
            position: relative;
            z-index: 1;
        }
        .statItem{
            text-align: center;
        }
        .statValue{
            font-size: 1.2rem;
            font-weight: 700;
            color: #ffffff;
        }
        .statLabel{
            font-size: 0.8rem;
            color: rgba(255,255,255,0.4);
            margin-top: 4px;
        }

        /* Period badge */
        .periodBadge{
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            background: rgba(255,255,255,0.04);
            border: 1px solid rgba(255,255,255,0.08);
            color: rgba(255,255,255,0.5);
            font-size: 0.85rem;
            margin-bottom: 32px;
            position: relative;
            z-index: 1;
        }
        .periodBadge i{ color: #31D2F7; }

        /* Buttons */
        .summaryActions{
            display: flex;
            flex-direction: column;
            gap: 12px;
            align-items: center;
            position: relative;
            z-index: 1;
        }
        .renewBtn{
            display: inline-flex;
            align-items: center;
            gap: 10px;
            padding: 16px 32px;
            border-radius: 14px;
            font-weight: 800;
            font-size: 1rem;
            background: linear-gradient(135deg, #44E0AC, #31D2F7);
            color: #04262a;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 8px 24px rgba(68,224,172,0.2);
        }
        .renewBtn:hover{
            transform: translateY(-2px);
            box-shadow: 0 12px 32px rgba(68,224,172,0.3);
        }
        .resetBtn{
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 24px;
            border-radius: 12px;
            font-weight: 700;
            font-size: 0.9rem;
            background: transparent;
            color: rgba(255,255,255,0.5);
            border: 1px solid rgba(255,255,255,0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            text-decoration: none;
        }
        .resetBtn:hover{
            background: rgba(255,255,255,0.04);
            border-color: rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.7);
            text-decoration: none;
        }

        /* Confetti canvas */
        #confettiCanvas{
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            z-index: 9999;
        }
    </style>

    @if($isSuccess)
        <canvas id="confettiCanvas"></canvas>
    @endif

    <section class="summaryWrap">
        <div class="summaryCard {{ $isSuccess ? 'success' : 'fail' }}">

            <div class="periodBadge">
                <i class="fa-solid fa-calendar"></i>
                {{ \Carbon\Carbon::parse($startDate)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($endDate)->format('d/m/Y') }}
            </div>

            <div class="summaryIcon">
                @if($isSuccess)
                    <i class="fa-solid fa-trophy"></i>
                @else
                    <i class="fa-solid fa-chart-line-down fa-solid fa-arrow-trend-down"></i>
                @endif
            </div>

            @if($isSuccess)
                <h1 class="summaryTitle">Complimenti!</h1>
                <p class="summaryDesc">
                    Hai rispettato il budget questo periodo. Ottimo lavoro nel gestire le tue finanze!
                </p>

                <div class="amountBig">+€{{ number_format(abs($saved), 2) }}</div>
                <div class="amountLabel">Risparmiato</div>
            @else
                <h1 class="summaryTitle">Budget superato</h1>
                <p class="summaryDesc">
                    Questo periodo non sei riuscito a rispettare il budget. Non preoccuparti, il prossimo periodo andrà meglio!
                </p>

                <div class="amountBig">-€{{ number_format(abs($saved), 2) }}</div>
                <div class="amountLabel">Eccedenza</div>
            @endif

            <div class="statsRow">
                <div class="statItem">
                    <div class="statValue">€{{ number_format($totalBudget, 2) }}</div>
                    <div class="statLabel">Budget</div>
                </div>
                <div class="statItem">
                    <div class="statValue">€{{ number_format($totalSpent, 2) }}</div>
                    <div class="statLabel">Speso</div>
                </div>
                <div class="statItem">
                    <div class="statValue">€{{ number_format($income, 2) }}</div>
                    <div class="statLabel">Entrate</div>
                </div>
            </div>

            <div class="summaryActions">
                <form action="{{ route('budget.renew-period') }}" method="POST">
                    @csrf
                    <button type="submit" class="renewBtn">
                        <i class="fa-solid fa-rotate"></i> Usa lo stesso budget per il prossimo periodo
                    </button>
                </form>

                <a href="{{ route('account-setup.step-one') }}" class="resetBtn">
                    <i class="fa-solid fa-sliders"></i> Crea un nuovo budget
                </a>
            </div>

        </div>
    </section>

    @if($isSuccess)
        <script>
            // Confetti animation
            (function() {
                const canvas = document.getElementById('confettiCanvas');
                if (!canvas) return;
                const ctx = canvas.getContext('2d');

                canvas.width = window.innerWidth;
                canvas.height = window.innerHeight;

                window.addEventListener('resize', () => {
                    canvas.width = window.innerWidth;
                    canvas.height = window.innerHeight;
                });

                const colors = ['#44E0AC', '#31D2F7', '#FABE58', '#ff6b6b', '#a78bfa', '#ffffff'];
                const particles = [];

                class Particle {
                    constructor() {
                        this.reset();
                        this.y = Math.random() * -canvas.height;
                    }
                    reset() {
                        this.x = Math.random() * canvas.width;
                        this.y = -10;
                        this.size = Math.random() * 8 + 4;
                        this.speedY = Math.random() * 3 + 2;
                        this.speedX = Math.random() * 2 - 1;
                        this.color = colors[Math.floor(Math.random() * colors.length)];
                        this.rotation = Math.random() * 360;
                        this.rotationSpeed = Math.random() * 6 - 3;
                        this.opacity = 1;
                        this.shape = Math.random() > 0.5 ? 'rect' : 'circle';
                    }
                    update() {
                        this.y += this.speedY;
                        this.x += this.speedX;
                        this.rotation += this.rotationSpeed;
                        this.speedX += Math.random() * 0.2 - 0.1;

                        if (this.y > canvas.height + 20) {
                            this.opacity -= 0.02;
                            if (this.opacity <= 0) this.reset();
                        }
                    }
                    draw() {
                        ctx.save();
                        ctx.translate(this.x, this.y);
                        ctx.rotate((this.rotation * Math.PI) / 180);
                        ctx.globalAlpha = Math.max(0, this.opacity);
                        ctx.fillStyle = this.color;

                        if (this.shape === 'rect') {
                            ctx.fillRect(-this.size / 2, -this.size / 4, this.size, this.size / 2);
                        } else {
                            ctx.beginPath();
                            ctx.arc(0, 0, this.size / 2, 0, Math.PI * 2);
                            ctx.fill();
                        }
                        ctx.restore();
                    }
                }

                // Crea particelle
                for (let i = 0; i < 150; i++) {
                    particles.push(new Particle());
                }

                let frameCount = 0;
                const maxFrames = 300; // ~5 secondi a 60fps

                function animate() {
                    ctx.clearRect(0, 0, canvas.width, canvas.height);

                    particles.forEach(p => {
                        p.update();
                        p.draw();
                    });

                    frameCount++;
                    if (frameCount < maxFrames) {
                        requestAnimationFrame(animate);
                    } else {
                        // Fade out finale
                        let fadeFrame = 0;
                        function fadeOut() {
                            ctx.clearRect(0, 0, canvas.width, canvas.height);
                            particles.forEach(p => {
                                p.opacity -= 0.03;
                                p.update();
                                p.draw();
                            });
                            fadeFrame++;
                            if (fadeFrame < 60) {
                                requestAnimationFrame(fadeOut);
                            } else {
                                canvas.remove();
                            }
                        }
                        fadeOut();
                    }
                }

                // Delay iniziale per effetto sorpresa
                setTimeout(() => animate(), 500);
            })();
        </script>
    @endif
@endsection

