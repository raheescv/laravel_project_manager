# CI/CD Pipeline Setup

This directory contains GitHub Actions workflows for automated building and deployment.

## Workflows

### 1. `build.yml` - Build on Commit
This workflow automatically runs `npm run build` on every commit to main, master, or develop branches.

**Features:**
- ✅ Automatically checks out code from git
- ✅ Installs npm dependencies
- ✅ Runs `npm run build`
- ✅ Uploads build artifacts for 7 days

**Triggers:**
- Push to `main`, `master`, or `develop` branches
- Pull requests to `main`, `master`, or `develop` branches

### 2. `deploy.yml` - Build and Deploy (Optional)
This workflow builds the project and optionally deploys to your server.

**Features:**
- ✅ All features from `build.yml`
- ✅ Optional automatic deployment to server via SSH
- ✅ Runs post-deployment Laravel optimizations

## Setup Instructions

### Basic Setup (Build Only)

The `build.yml` workflow works out of the box - no configuration needed! It will automatically:
1. Pull the latest code from git
2. Install dependencies
3. Run `npm run build`

### Advanced Setup (With Auto-Deployment)

If you want automatic deployment to your server, you need to configure GitHub Secrets:

1. Go to your GitHub repository
2. Navigate to **Settings** → **Secrets and variables** → **Actions**
3. Add the following secrets:

   - `DEPLOY_HOST`: Your server hostname or IP (e.g., `your-server.com`)
   - `DEPLOY_USER`: SSH username (e.g., `ubuntu` or `root`)
   - `DEPLOY_SSH_KEY`: Your private SSH key for server access
   - `DEPLOY_PORT`: SSH port (optional, defaults to 22)

4. To generate an SSH key pair (if you don't have one):
   ```bash
   ssh-keygen -t ed25519 -C "github-actions"
   # Copy the public key to your server:
   ssh-copy-id -i ~/.ssh/id_ed25519.pub user@your-server.com
   # Add the private key content to GitHub Secrets as DEPLOY_SSH_KEY
   ```

### Testing the Workflow

1. Make a commit and push to your repository:
   ```bash
   git add .
   git commit -m "Test CI/CD pipeline"
   git push origin main
   ```

2. Check the workflow status:
   - Go to your GitHub repository
   - Click on the **Actions** tab
   - You should see the workflow running

### Viewing Build Artifacts

After a successful build:
1. Go to the **Actions** tab in GitHub
2. Click on the completed workflow run
3. Scroll down to **Artifacts** section
4. Download the build files if needed

## Customization

### Change Trigger Branches

Edit the workflow files and modify the `branches` section:
```yaml
on:
  push:
    branches:
      - main
      - your-branch-name
```

### Add More Build Steps

You can add additional steps before or after the build:
```yaml
- name: Run tests
  run: npm test

- name: Lint code
  run: npm run lint
```

### Modify Deployment Commands

Edit the `deploy.yml` file and modify the `script` section in the "Run post-deployment commands" step.

## Troubleshooting

### Build Fails
- Check Node.js version compatibility
- Ensure all dependencies are in `package.json`
- Check the Actions tab for detailed error messages

### Deployment Fails
- Verify SSH keys are correctly set up
- Check server permissions
- Ensure the deployment path exists on the server
- Verify all secrets are correctly configured

## Notes

- Build artifacts are kept for 7 days
- The workflow ignores changes to `.md` files to avoid unnecessary builds
- For production deployments, consider using the `deploy.yml` workflow
- The deployment workflow only runs on `main` or `master` branches
