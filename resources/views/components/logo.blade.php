<img src="{{ asset('images/LOGO-FEDERMANAGER.png') }}" alt="Logo" style="height: 29px; width: 220px;">
{{--
  The key change is adding 'width: auto;'.
  This tells the browser to calculate the width based on the set height (2.5rem)
  while maintaining the image's original aspect ratio.

  You can adjust '2.5rem' to your desired height.
  If you want to constrain by width instead, you could use:
  style="width: 150px; height: auto;" (replace 150px with your desired width)

  Alternatively, if the logo is inside a container that has a defined width,
  and you want the logo to fill that width proportionally:
  style="max-width: 100%; height: auto; display: block;"
--}}