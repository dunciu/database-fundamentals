<?php include_once("header.php")?>

<div class="container">
<h2 class="my-3">Register new account</h2>

<form method="POST" action="process_registration.php">
  <div class="form-group row">
    <label class="col-sm-2 col-form-label text-right">Registering as a:</label>
    <div class="col-sm-10">
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="isBuyer" id="isBuyer" value="1" checked>
        <label class="form-check-label" for="isBuyer">Buyer</label>
      </div>
      <div class="form-check form-check-inline">
        <input class="form-check-input" type="checkbox" name="isSeller" id="isSeller" value="1">
        <label class="form-check-label" for="isSeller">Seller</label>
      </div>
      <small class="form-text text-muted"><span class="text-danger">* Choose at least one.</span> You can be both.</small>
    </div>
  </div>
  <div class="form-group row">
    <label for="firstName" class="col-sm-2 col-form-label text-right">First name</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="firstName" name="firstName" maxlength="50" required>
    </div>
  </div>
  <div class="form-group row">
    <label for="lastName" class="col-sm-2 col-form-label text-right">Last name</label>
    <div class="col-sm-10">
      <input type="text" class="form-control" id="lastName" name="lastName" maxlength="50" required>
    </div>
  </div>
  <div class="form-group row">
    <label for="email" class="col-sm-2 col-form-label text-right">Email</label>
    <div class="col-sm-10">
      <input type="email" class="form-control" id="email" name="email" maxlength="255" required>
    </div>
  </div>
  <div class="form-group row">
    <label for="password" class="col-sm-2 col-form-label text-right">Password</label>
    <div class="col-sm-10">
      <input type="password" class="form-control" id="password" name="password" minlength="8" required>
      <small class="form-text text-muted">At least 8 characters.</small>
    </div>
  </div>
  <div class="form-group row">
    <label for="passwordConfirmation" class="col-sm-2 col-form-label text-right">Repeat password</label>
    <div class="col-sm-10">
      <input type="password" class="form-control" id="passwordConfirmation" name="passwordConfirmation" minlength="8" required>
    </div>
  </div>
  <div class="form-group row">
    <button type="submit" class="btn btn-primary form-control">Register</button>
  </div>
</form>

<div class="text-center">Already have an account? <a href="" data-toggle="modal" data-target="#loginModal">Login</a></div>
</div>

<?php include_once("footer.php")?>