export default function filterItems( items, filter ) {
  if ( filter == '' ) {
    return items;
  } else {
    filter = filter.toUpperCase();
    return items.filter( item => item.name.toUpperCase().includes( filter ) );
  }
}
