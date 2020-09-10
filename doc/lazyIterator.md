# LazyIterator

> 📖 back to [readme](../readme.md)

an iterator that will wrap a collection provider function and only call it once actually needed

- when an iterable collection must be passed somewhere but the collection has not yet been fetched
- when mapping of the elements of the set is needed, but the set is lazy-loaded itself (may save memory)
- useful for wrapping api calls (in certain cases)

Good, when you need to pass a result of an API call
to a component iterating over the returned collection only on certain conditions
that are not directly managed at the moment of passing of the result.
In traditional way the call could be wasted.

With `LazyIterator` you can wrap the call to a callable and create LazyIterator
that is then passed to the component for rendering.
You can be sure the API only gets called when the result is actually needed.

Furthermore, you can also apply a number of mapping functions in a manner
similar to `array_map` function.
