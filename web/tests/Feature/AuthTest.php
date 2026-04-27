<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class AuthTest extends TestCase
{
    // テスト実行時にデータベースをテスト用に初期化し、終わったら綺麗に戻す機能
    use RefreshDatabase;

    /**
     * ログイン画面が正常に表示されるかをテストします。
     */
    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * 正しい情報でログインできるかをテストします。
     */
    public function test_user_can_login_with_correct_credentials(): void
    {
        // 1. テスト専用のダミーユーザーをデータベースに作成します
        $user = User::create([
            'username' => 'testadmin',
            'email' => 'testadmin@example.com', // テーブル設計に合わせて必須のemailも追加します
            'password' => 'password123', // Userモデルのキャスト機能で自動的にハッシュ化されます
        ]);

        // 2. ログインAPIに対して、作成したユーザー情報でPOSTリクエストを送信します
        $response = $this->post('/login', [
            'username' => 'testadmin',
            'password' => 'password123',
        ]);

        // 3. レスポンスが成功（200 OK）し、JSONで {'success': true} が返ることを確認します
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // 4. 実際にシステム上でログイン状態になっているかを確認します
        $this->assertAuthenticatedAs($user);
    }

    /**
     * 誤ったパスワードでログインできないかをテストします。
     */
    public function test_user_cannot_login_with_incorrect_password(): void
    {
        $user = User::create([
            'username' => 'testadmin',
            'email' => 'testadmin@example.com',
            'password' => 'password123',
        ]);

        $response = $this->post('/login', [
            'username' => 'testadmin',
            'password' => 'wrongpassword', // わざと間違えたパスワードを送る
        ]);

        // 認証失敗のステータスコード(401)と、JSONで {'success': false} が返ることを確認
        $response->assertStatus(401)
                 ->assertJson(['success' => false]);

        // システム上でログイン状態になっていない（ゲスト状態である）ことを確認
        $this->assertGuest();
    }

    /**
     * 新規アカウントを作成できるかをテストします。
     */
    public function test_user_can_register_new_account(): void
    {
        $response = $this->post('/register', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'confirm_password' => 'password123',
        ]);

        // 登録成功のステータスコード(200)と、success: true が返ることを確認
        $response->assertStatus(200)
                 ->assertJson(['success' => true]);

        // 実際にデータベースの users テーブルにデータが保存されたかを確認
        $this->assertDatabaseHas('users', [
            'username' => 'newuser',
            'email' => 'newuser@example.com',
        ]);
    }

    /**
     * 未ログインのユーザーが保護されたページ（ダッシュボード）にアクセスできないかをテストします。
     */
    public function test_unauthenticated_user_cannot_access_dashboard(): void
    {
        // ログインせずにダッシュボードへアクセス
        $response = $this->get('/dashboard');

        // Laravelの auth ミドルウェアによって弾かれ、ログイン画面 ( / ) にリダイレクトされることを確認
        $response->assertRedirect('/');
    }
}
//テストもっと書かないといけない。