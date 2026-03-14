# Deploy Midnight Travel Invoices — GitHub + cPanel

## Part 1: Connect to GitHub

### 1.1 Initialize Git and commit (if not done)

In your project folder:

```bash
cd "d:\Web Development Projects\Inovice Project"
git init
git add .
git commit -m "Initial commit: Midnight Travel invoice app"
```

### 1.2 Create a repository on GitHub

1. Go to [github.com](https://github.com) and sign in.
2. Click **New repository** (or **+** → **New repository**).
3. Set:
   - **Repository name:** e.g. `midnight-travel-invoices`
   - **Visibility:** Private or Public
   - Do **not** add a README, .gitignore, or license (you already have them).
4. Click **Create repository**.

### 1.3 Push your code to GitHub

GitHub will show commands; use these (replace `YOUR_USERNAME` and `YOUR_REPO` with your repo path):

```bash
git remote add origin https://github.com/YOUR_USERNAME/YOUR_REPO.git
git branch -M main
git push -u origin main
```

If GitHub suggests `master` instead of `main`, use `master` in the second command and push that.

**Using SSH (optional):**

```bash
git remote add origin git@github.com:YOUR_USERNAME/YOUR_REPO.git
git branch -M main
git push -u origin main
```

Your project is now connected to GitHub.

---

## Part 2: Connect cPanel to GitHub and deploy

### 2.1 Open Git in cPanel

1. Log in to **cPanel**.
2. Find **Git™ Version Control** (or **Git Version Control** / **Application Manager**).
3. Open it.

### 2.2 Clone the GitHub repository

1. Click **Create** or **Clone a Repository**.
2. Fill in:
   - **Repository URL:**  
     `https://github.com/YOUR_USERNAME/YOUR_REPO.git`  
     (or your repo’s **Clone with HTTPS** URL from GitHub)
   - **Repository Path:**  
     Directory where the app should live, e.g.:
     - `midnight-travel-invoices` (in your home directory), or  
     - `public_html/midnight-travel-invoices` if you want it under the web root
3. If the repo is **private:** use **Clone with HTTPS** and add your GitHub username and a **Personal Access Token** (not your password) when cPanel asks for credentials.  
   To create a token: GitHub → **Settings** → **Developer settings** → **Personal access tokens** → **Generate new token**; give it `repo` scope.
4. Click **Create** / **Clone**.

cPanel will clone the repo. You can use **Pull** or **Update** in Git Version Control to deploy new changes after you push to GitHub.

### 2.3 Run the Node.js app on cPanel

This app is **Node.js + Express**, not plain PHP/HTML, so it must run as a Node app:

1. In cPanel, open **Setup Node.js App** (or **Application Manager** → **Node.js**).
2. Click **Create Application**:
   - **Node.js version:** e.g. 18.x or 20.x.
   - **Application root:** same path you used for the repo (e.g. `midnight-travel-invoices`).
   - **Application URL:** choose a subdomain or folder (e.g. `invoices.yourdomain.com` or `yourdomain.com/invoices`).
   - **Application startup file:** `server.js`.
3. Save.

4. In the same screen:
   - **Run NPM install** so `node_modules` is created.
   - Set **Environment variables** if your host allows (e.g. `SESSION_SECRET`, `PORT`, `NODE_ENV=production`). Otherwise use a `.env` file (see below).
   - Start the application.

5. **Environment on cPanel:**  
   If cPanel doesn’t use `.env`, create a `.env` in the application root (same folder as `server.js`) with:

   ```env
   NODE_ENV=production
   SESSION_SECRET=your-long-random-secret-here
   PORT=3000
   DATABASE_PATH=./data/invoices.db
   ```

   Then run the seed once (via SSH or cPanel’s “Run script” if available):

   ```bash
   cd ~/midnight-travel-invoices   # or your path
   node scripts/seed.js
   ```

6. **Open the app:**  
   Use the URL you set (e.g. `https://invoices.yourdomain.com`). If the port is different, cPanel usually sets up a proxy so you still use port 80/443 in the browser.

### 2.4 Deploying updates (after you push to GitHub)

1. In cPanel → **Git Version Control**, select your repository.
2. Click **Pull** or **Update** to get the latest code from GitHub.
3. In **Setup Node.js App**, run **NPM install** again if `package.json` changed, then **Restart** the application.

---

## Quick reference

| Step | Where | Action |
|------|--------|--------|
| 1 | Your PC | `git init`, `git add .`, `git commit` |
| 2 | GitHub | Create new repo, copy clone URL |
| 3 | Your PC | `git remote add origin <URL>`, `git push -u origin main` |
| 4 | cPanel | Git Version Control → Clone repo (HTTPS + token for private) |
| 5 | cPanel | Setup Node.js App → Create app, run NPM install, set env, start |
| 6 | cPanel | Run `node scripts/seed.js` once (SSH or script) |
| Updates | cPanel | Git → Pull; Node.js App → NPM install if needed, Restart |

---

## Security notes for production

- Use a **strong random `SESSION_SECRET`** in production.
- Keep `.env` out of the repo (it’s in `.gitignore`). Create `.env` directly on the server.
- For private GitHub repo, use a **Personal Access Token** with minimal scope (`repo`) and do not commit it anywhere.
